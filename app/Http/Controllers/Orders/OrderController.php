<?php

declare(strict_types=1);

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\IfdsReadOnly\CustomerOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    // ── GET /v1/orders ─────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $customer = Auth::user();

        if (!$customer->isSyncedToIfds()) {
            return response()->json(['data' => [], 'meta' => ['total' => 0, 'message' => 'Account not yet synced.']]);
        }

        $ifds_cid = (int) $customer->ifds_customer_id;

        $query = CustomerOrder::forCustomer($ifds_cid);

        // Optional filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from')) {
            $query->where('order_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('order_date', '<=', $request->to);
        }

        $orders = $query->orderByDesc('order_date')->paginate(15);

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    // ── POST /v1/orders ────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $customer = Auth::user();

        if (!$customer->isSyncedToIfds()) {
            return response()->json(['message' => 'Account not yet activated. Please contact support.'], 422);
        }

        $validated = $request->validate([
            'product'                => 'required|string|in:HSD,Diesel,Petrol',
            'delivery_location_id'   => 'required|string',
            'quantity'               => 'required|numeric|min:1|max:999999',
            'preferred_date'         => 'required|date|after_or_equal:today',
            'preferred_time'         => 'nullable|string',
            'payment_mode'           => 'nullable|string|in:credit,prepaid,upi,cash',
            'special_instructions'   => 'nullable|string|max:1000',
        ]);

        // Map portal product name → ifds product_id
        $productMap = ['HSD' => 1, 'Diesel' => 1, 'Petrol' => 2];
        $productId  = $productMap[$validated['product']] ?? 1;

        // Fetch delivery location from BFF customer_addresses table
        $location = \DB::connection('customer')
            ->table('customer_addresses')
            ->where('id', $validated['delivery_location_id'])
            ->where('customer_id', $customer->id)
            ->first();

        if (!$location) {
            return response()->json(['message' => 'Delivery location not found.'], 422);
        }

        // Get a service-account JWT (cached for 50 minutes)
        $serviceToken = Cache::remember('portal_service_jwt', 3000, function () {
            $base = rtrim(config('services.ifds.base_url'), '/');
            $res  = Http::post("{$base}/api/v1/auth/login", [
                'email'    => 'portal-service@fuelflow.in',
                'password' => config('services.ifds.service_password'),
            ]);
            if (!$res->successful()) {
                throw new \RuntimeException('Portal service auth failed: ' . $res->body());
            }
            return $res->json('access_token');
        });

        // Build ifds order payload using BFF address fields
        $payload = [
            'customer_id'              => $customer->ifds_customer_id,
            'product_id'               => $productId,
            'quantity_ordered'         => $validated['quantity'],
            'delivery_address'         => $location->address,
            'delivery_city'            => 'N/A',
            'delivery_state'           => 'Maharashtra',
            'delivery_pincode'         => '000000',
            'delivery_contact_person'  => $location->contact_person ?? $customer->name,
            'delivery_contact_phone'   => $location->contact_mobile ?? $customer->mobile,
            'requested_delivery_date'  => $validated['preferred_date'],
            'requested_delivery_time'  => $validated['preferred_time'] ?? null,
            'payment_terms'            => $validated['payment_mode'] === 'credit' ? 'credit_30' : 'cash',
            'special_instructions'     => $validated['special_instructions'] ?? null,
            'order_source'             => 'portal',
            'order_channel'            => 'portal',
        ];

        $base = rtrim(config('services.ifds.base_url'), '/');
        $res  = Http::withToken($serviceToken)->timeout(10)->post("{$base}/api/v1/orders", $payload);

        if (!$res->successful()) {
            Log::warning('Portal order placement failed', [
                'status'      => $res->status(),
                'body'        => $res->body(),
                'customer_id' => $customer->id,
            ]);

            // Surface validation errors from ifds if available
            $errors = $res->json('errors') ?? $res->json('error') ?? $res->json('message') ?? 'Failed to place order.';
            return response()->json(['message' => $errors], $res->status() >= 500 ? 500 : 422);
        }

        $order = $res->json('data');

        return response()->json(['data' => $order, 'message' => 'Order placed successfully.'], 201);
    }

    // ── GET /v1/orders/{orderNumber} ───────────────────────────────────────────

    public function show(string $orderNumber): JsonResponse
    {
        $customer = Auth::user();

        if (!$customer->isSyncedToIfds()) {
            return response()->json(['error' => 'Account not yet synced.'], 404);
        }

        $ifds_cid = (int) $customer->ifds_customer_id;

        $order = CustomerOrder::forCustomer($ifds_cid)
            ->where('order_number', $orderNumber)
            ->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found.'], 404);
        }

        return response()->json(['data' => $order]);
    }
}
