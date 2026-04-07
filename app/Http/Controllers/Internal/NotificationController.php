<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\CustomerDb\BffNotification;
use App\Models\CustomerDb\CustomerAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    /**
     * POST /api/v1/internal/notifications
     *
     * Called by ifds when it needs to push an in-app notification to a customer.
     * Payload identifies the customer by ifds_customer_id (integer).
     *
     * Secured with HMAC-SHA256 on the JSON body (same shared secret as sync-result).
     */
    public function push(Request $request): JsonResponse
    {
        // ── HMAC verification ───────────────────────────────────────────────
        $secret    = config('services.bff.internal_secret');
        $signature = $request->header('X-BFF-Signature');
        $body      = $request->getContent();

        if (!$signature || !hash_equals(hash_hmac('sha256', $body, $secret), $signature)) {
            Log::warning('BFF notifications: invalid signature', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        // ── Validate ────────────────────────────────────────────────────────
        $validated = $request->validate([
            'ifds_customer_id' => ['required', 'integer', 'min:1'],
            'type'             => ['required', 'string', 'max:50'],
            'title'            => ['required', 'string', 'max:255'],
            'body'             => ['nullable', 'string', 'max:1000'],
            'data'             => ['nullable', 'array'],
        ]);

        // ── Find BFF customer by ifds_customer_id ───────────────────────────
        $customer = CustomerAccount::where('ifds_customer_id', $validated['ifds_customer_id'])
            ->where('ifds_synced', true)
            ->first();

        if (!$customer) {
            // Customer not yet synced or not found — silently ignore (202)
            return response()->json(['ok' => true, 'queued' => false]);
        }

        // ── Persist notification ─────────────────────────────────────────────
        $notification = BffNotification::create([
            'id'          => Str::uuid()->toString(),
            'customer_id' => $customer->id,
            'type'        => $validated['type'],
            'title'       => strip_tags($validated['title']),
            'body'        => isset($validated['body']) ? strip_tags($validated['body']) : null,
            'data'        => $validated['data'] ?? null,
        ]);

        // ── Broadcast over WebSocket (if Reverb is running) ─────────────────
        // Uses the same private-customer.{ifds_customer_id} channel
        try {
            broadcast(new \App\Events\CustomerNotificationPushed(
                customerId:       (int) $customer->ifds_customer_id,
                notificationId:   $notification->id,
                type:             $notification->type,
                title:            $notification->title,
                body:             $notification->body,
                data:             $notification->data ?? [],
                createdAt:        $notification->created_at?->toIso8601String() ?? now()->toIso8601String(),
            ));
        } catch (\Throwable $e) {
            // Reverb may not be running in dev — notification is still persisted
            Log::debug('Reverb broadcast skipped: ' . $e->getMessage());
        }

        return response()->json(['ok' => true, 'queued' => true, 'notification_id' => $notification->id], 201);
    }
}
