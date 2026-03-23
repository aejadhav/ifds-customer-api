<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Models\CustomerDb\BffNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    // ── GET /v1/notifications ──────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $customer = Auth::user();

        $query = BffNotification::where('customer_id', $customer->id)
            ->orderByDesc('created_at');

        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        $notifications = $query->paginate(20);

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'current_page'  => $notifications->currentPage(),
                'last_page'     => $notifications->lastPage(),
                'total'         => $notifications->total(),
                'unread_count'  => BffNotification::where('customer_id', $customer->id)
                    ->whereNull('read_at')
                    ->count(),
            ],
        ]);
    }

    // ── GET /v1/notifications/unread-count ─────────────────────────────────────

    public function unreadCount(): JsonResponse
    {
        $count = BffNotification::where('customer_id', Auth::id())
            ->whereNull('read_at')
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    // ── POST /v1/notifications/{id}/read ──────────────────────────────────────

    public function markRead(string $id): JsonResponse
    {
        $customer = Auth::user();

        $updated = BffNotification::where('customer_id', $customer->id)
            ->where('id', $id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'ok'      => $updated > 0,
            'message' => $updated ? 'Marked as read.' : 'Already read or not found.',
        ]);
    }

    // ── POST /v1/notifications/read-all ───────────────────────────────────────

    public function markAllRead(): JsonResponse
    {
        $customer = Auth::user();

        $count = BffNotification::where('customer_id', $customer->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true, 'marked' => $count]);
    }

    // ── DELETE /v1/notifications/{id} ─────────────────────────────────────────

    public function destroy(string $id): JsonResponse
    {
        $customer = Auth::user();

        BffNotification::where('customer_id', $customer->id)
            ->where('id', $id)
            ->delete();

        return response()->json(['ok' => true]);
    }
}
