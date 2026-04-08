<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\IfdsReadOnly\CustomerInvoice;
use App\Models\IfdsReadOnly\CustomerOrder;
use App\Models\IfdsReadOnly\CustomerPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * GET /v1/reports/summary
     *
     * Returns aggregated spend, volume, order count, avg rate, monthly trend,
     * and location breakdown for the authenticated customer over a given period.
     *
     * Query params:
     *   period  — this_month | 3months | 6months | this_year | last_year (default: this_month)
     */
    public function summary(Request $request): JsonResponse
    {
        $customer = Auth::user();

        if (!$customer->isSyncedToIfds()) {
            return response()->json([
                'summary'            => $this->emptySummary(),
                'monthly_trend'      => [],
                'location_breakdown' => [],
                'payment_summary'    => $this->emptyPaymentSummary(),
            ]);
        }

        $ifds_cid = (int) $customer->ifds_customer_id;
        [$from, $to] = $this->periodRange($request->input('period', 'this_month'));

        // ── Order aggregates ─────────────────────────────────────────────────
        $orderBase = CustomerOrder::forCustomer($ifds_cid)
            ->whereBetween('order_date', [$from, $to])
            ->whereNotIn('status', ['cancelled', 'rejected']);

        $orderAgg = (clone $orderBase)->selectRaw(
            'COUNT(*) as total_orders,
             COALESCE(SUM(quantity_ordered), 0) as total_volume,
             COALESCE(SUM(total_amount), 0) as total_amount,
             COALESCE(AVG(CASE WHEN quantity_ordered > 0 THEN total_amount / quantity_ordered END), 0) as avg_rate'
        )->first();

        // ── Monthly trend (orders grouped by month) ──────────────────────────
        $monthlyTrend = (clone $orderBase)
            ->selectRaw(
                "TO_CHAR(order_date, 'Mon YY') as month,
                 DATE_TRUNC('month', order_date) as month_start,
                 COUNT(*) as orders,
                 COALESCE(SUM(quantity_ordered), 0) as volume,
                 COALESCE(SUM(total_amount), 0) as amount"
            )
            ->groupByRaw("DATE_TRUNC('month', order_date), TO_CHAR(order_date, 'Mon YY')")
            ->orderByRaw("DATE_TRUNC('month', order_date)")
            ->get()
            ->map(fn($row) => [
                'month'   => $row->month,
                'orders'  => (int) $row->orders,
                'volume'  => (float) $row->volume,
                'amount'  => (float) $row->amount,
            ]);

        // ── Location breakdown ───────────────────────────────────────────────
        $locationRows = (clone $orderBase)
            ->selectRaw(
                "COALESCE(NULLIF(TRIM(delivery_address), ''), 'Unknown') as location,
                 COUNT(*) as orders,
                 COALESCE(SUM(quantity_ordered), 0) as volume,
                 COALESCE(SUM(total_amount), 0) as amount"
            )
            ->groupByRaw("COALESCE(NULLIF(TRIM(delivery_address), ''), 'Unknown')")
            ->orderByRaw('SUM(quantity_ordered) DESC')
            ->limit(5)
            ->get();

        $totalVolume = (float) ($orderAgg->total_volume ?? 0);
        $locationBreakdown = $locationRows->map(fn($row) => [
            'name'    => $row->location,
            'orders'  => (int) $row->orders,
            'volume'  => (float) $row->volume,
            'amount'  => (float) $row->amount,
            'percent' => $totalVolume > 0 ? round((float) $row->volume / $totalVolume * 100) : 0,
        ]);

        // ── Payment summary ──────────────────────────────────────────────────
        $invoiceAgg = CustomerInvoice::forCustomer($ifds_cid)
            ->selectRaw(
                "COUNT(*) as total_invoices,
                 COALESCE(SUM(CASE WHEN payment_status IN ('unpaid','partial') THEN balance_amount ELSE 0 END), 0) as outstanding,
                 COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) as paid_amount"
            )
            ->first();

        return response()->json([
            'summary' => [
                'orders'     => (int) ($orderAgg->total_orders ?? 0),
                'volume'     => (float) ($orderAgg->total_volume ?? 0),
                'amount'     => (float) ($orderAgg->total_amount ?? 0),
                'avg_rate'   => round((float) ($orderAgg->avg_rate ?? 0), 2),
            ],
            'monthly_trend'      => $monthlyTrend,
            'location_breakdown' => $locationBreakdown,
            'payment_summary'    => [
                'total_invoices' => (int) ($invoiceAgg->total_invoices ?? 0),
                'outstanding'    => (float) ($invoiceAgg->outstanding ?? 0),
                'paid_amount'    => (float) ($invoiceAgg->paid_amount ?? 0),
            ],
            'period' => [
                'from'  => $from,
                'to'    => $to,
                'label' => $this->periodLabel($request->input('period', 'this_month')),
            ],
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function periodRange(string $period): array
    {
        $now = now();
        return match ($period) {
            '3months'   => [$now->copy()->subMonths(3)->startOfMonth()->toDateString(), $now->toDateString()],
            '6months'   => [$now->copy()->subMonths(6)->startOfMonth()->toDateString(), $now->toDateString()],
            'this_year' => [$now->copy()->startOfYear()->toDateString(), $now->toDateString()],
            'last_year' => [$now->copy()->subYear()->startOfYear()->toDateString(), $now->copy()->subYear()->endOfYear()->toDateString()],
            default     => [$now->copy()->startOfMonth()->toDateString(), $now->toDateString()], // this_month
        };
    }

    private function periodLabel(string $period): string
    {
        return match ($period) {
            '3months'   => 'Last 3 Months',
            '6months'   => 'Last 6 Months',
            'this_year' => 'This Year',
            'last_year' => 'Last Year',
            default     => 'This Month',
        };
    }

    private function emptySummary(): array
    {
        return ['orders' => 0, 'volume' => 0, 'amount' => 0, 'avg_rate' => 0];
    }

    private function emptyPaymentSummary(): array
    {
        return ['total_invoices' => 0, 'outstanding' => 0, 'paid_amount' => 0];
    }
}
