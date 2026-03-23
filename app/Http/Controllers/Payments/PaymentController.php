<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\IfdsReadOnly\CustomerInvoice;
use App\Models\IfdsReadOnly\CustomerPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    // ── GET /v1/invoices ───────────────────────────────────────────────────────

    public function invoices(Request $request): JsonResponse
    {
        $customer = Auth::user();

        if (!$customer->isSyncedToIfds()) {
            return response()->json(['data' => [], 'meta' => ['total' => 0]]);
        }

        $ifds_cid = (int) $customer->ifds_customer_id;

        $query = CustomerInvoice::forCustomer($ifds_cid);

        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }

        $invoices = $query->orderByDesc('invoice_date')->paginate(15);

        return response()->json([
            'data' => $invoices->items(),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page'    => $invoices->lastPage(),
                'per_page'     => $invoices->perPage(),
                'total'        => $invoices->total(),
            ],
        ]);
    }

    // ── GET /v1/invoices/{id} ──────────────────────────────────────────────────

    public function showInvoice(int $id): JsonResponse
    {
        $customer = Auth::user();

        if (!$customer->isSyncedToIfds()) {
            return response()->json(['error' => 'Account not yet synced.'], 404);
        }

        $invoice = CustomerInvoice::forCustomer((int) $customer->ifds_customer_id)
            ->where('id', $id)
            ->first();

        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found.'], 404);
        }

        return response()->json(['data' => $invoice]);
    }

    // ── GET /v1/payments ───────────────────────────────────────────────────────

    public function payments(Request $request): JsonResponse
    {
        $customer = Auth::user();

        if (!$customer->isSyncedToIfds()) {
            return response()->json(['data' => [], 'meta' => ['total' => 0]]);
        }

        $ifds_cid = (int) $customer->ifds_customer_id;

        $query = CustomerPayment::forCustomer($ifds_cid);

        if ($request->filled('from')) {
            $query->where('payment_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('payment_date', '<=', $request->to);
        }

        $payments = $query->orderByDesc('payment_date')->paginate(15);

        return response()->json([
            'data' => $payments->items(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page'    => $payments->lastPage(),
                'per_page'     => $payments->perPage(),
                'total'        => $payments->total(),
            ],
        ]);
    }
}
