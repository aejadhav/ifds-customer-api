<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Jobs\Payment\DispatchOfflinePaymentToIfdsJob;
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

    // ── POST /v1/payments ─────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $customer = Auth::user();

        if (!$customer->isSyncedToIfds()) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not yet activated. Please contact support.',
            ], 422);
        }

        $validated = $request->validate([
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:upi,neft,rtgs,cheque,cash',
            'payment_date'   => 'required|date|before_or_equal:today',
            'transaction_id' => 'nullable|string|max:100',
            'upi_id'         => 'nullable|string|max:100',
            'bank_name'      => 'nullable|string|max:100',
            'cheque_number'  => 'nullable|string|max:50',
            'notes'          => 'nullable|string|max:500',
            'invoice_ids'    => 'nullable|array',
            'invoice_ids.*'  => 'integer',
        ]);

        DispatchOfflinePaymentToIfdsJob::dispatch(
            ifdsCustomerId: (int) $customer->ifds_customer_id,
            bffCustomerId:  $customer->id,
            amount:         (float) $validated['amount'],
            paymentMethod:  $validated['payment_method'],
            paymentDate:    $validated['payment_date'],
            transactionId:  $validated['transaction_id'] ?? null,
            upiId:          $validated['upi_id'] ?? null,
            bankName:       $validated['bank_name'] ?? null,
            chequeNumber:   $validated['cheque_number'] ?? null,
            notes:          $validated['notes'] ?? null,
            invoiceIds:     $validated['invoice_ids'] ?? null,
        )->onQueue(config('services.bff.ifds_queue', 'bff_customer'));

        \Illuminate\Support\Facades\Log::info('Customer payment submitted', [
            'customer_id' => $customer->id,
            'ip'          => request()->ip(),
            'amount'      => $validated['amount'],
            'method'      => $validated['payment_method'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment submitted successfully. It will be verified by our team within 1 business day.',
        ], 202);
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
