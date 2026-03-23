<?php

declare(strict_types=1);

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\IfdsReadOnly\CustomerOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
