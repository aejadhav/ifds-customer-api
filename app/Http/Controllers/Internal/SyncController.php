<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\CustomerDb\CustomerAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    /**
     * POST /api/v1/internal/sync-result
     *
     * Called by the ifds RegisterCustomerFromBffJob after successfully
     * creating/linking the customer record in ifds_local.
     *
     * Secured with HMAC-SHA256 on the JSON payload body.
     */
    public function syncResult(Request $request): JsonResponse
    {
        // ── HMAC verification ───────────────────────────────────────────────
        $secret    = config('services.bff.internal_secret');
        $signature = $request->header('X-BFF-Signature');
        $body      = $request->getContent();

        if (!$signature || !hash_equals(hash_hmac('sha256', $body, $secret), $signature)) {
            Log::warning('BFF sync-result: invalid signature', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        // ── Validate payload ────────────────────────────────────────────────
        $validated = $request->validate([
            'bff_customer_id'  => ['required', 'string', 'uuid'],
            'ifds_customer_id' => ['required', 'integer', 'min:1'],
        ]);

        // ── Update BFF identity record ──────────────────────────────────────
        $updated = CustomerAccount::where('id', $validated['bff_customer_id'])
            ->whereNull('ifds_customer_id')          // idempotent — only update once
            ->update([
                'ifds_customer_id' => $validated['ifds_customer_id'],
                'ifds_synced'      => true,
                'status'           => 'active',
            ]);

        if (!$updated) {
            // Already synced or not found — still return 200 for idempotency
            Log::info('BFF sync-result: already synced or not found', $validated);
        }

        return response()->json(['ok' => true]);
    }
}
