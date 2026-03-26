<?php

declare(strict_types=1);

namespace App\Http\Controllers\Broadcasting;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BroadcastAuthController extends Controller
{
    /**
     * POST /api/v1/broadcasting/auth
     *
     * The portal sends the Pusher channel auth request here (with Bearer JWT).
     * Portal subscribes to private-customer.{ifds_customer_id} directly.
     * We verify the customer owns that channel, then sign the auth token.
     */
    public function auth(Request $request): JsonResponse
    {
        $customer = Auth::user();

        $channelName = $request->input('channel_name', '');
        $socketId    = $request->input('socket_id');

        Log::info('Broadcasting auth attempt', [
            'customer'         => $customer->id,
            'ifds_synced'      => $customer->ifds_synced,
            'ifds_customer_id' => $customer->ifds_customer_id,
            'channel'          => $channelName,
            'socket_id'        => $socketId,
        ]);

        if (!$customer->isSyncedToIfds()) {
            return response()->json(['error' => 'Account not synced yet.'], 403);
        }

        // Validate the customer is subscribing to their own channel only
        $expectedChannel = 'private-customer.' . $customer->ifds_customer_id;
        if ($channelName !== $expectedChannel) {
            Log::warning('Channel mismatch', ['expected' => $expectedChannel, 'got' => $channelName]);
            return response()->json(['error' => 'Unauthorized channel.'], 403);
        }

        // Sign the channel auth using the Reverb app secret
        $secret    = config('broadcasting.connections.pusher.secret');
        $signature = hash_hmac('sha256', "{$socketId}:{$channelName}", $secret);
        $authToken = config('broadcasting.connections.pusher.key') . ':' . $signature;

        Log::info('Broadcasting auth', [
            'customer'  => $customer->id,
            'channel'   => $channelName,
            'socket_id' => $socketId,
        ]);

        return response()->json(['auth' => $authToken]);
    }
}
