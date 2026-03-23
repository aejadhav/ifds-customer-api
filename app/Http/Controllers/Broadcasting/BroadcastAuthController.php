<?php

declare(strict_types=1);

namespace App\Http\Controllers\Broadcasting;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BroadcastAuthController extends Controller
{
    /**
     * POST /api/v1/broadcasting/auth
     *
     * The portal sends the Pusher channel auth request here (with Bearer JWT).
     * We verify identity, rewrite the channel name using the ifds_customer_id,
     * then proxy the auth request to the Reverb server.
     *
     * Channel name format from portal: private-customer.{bff_customer_id}
     * Reverb expects:                  private-customer.{ifds_customer_id}
     */
    public function auth(Request $request): JsonResponse
    {
        $customer = Auth::user();

        if (!$customer->isSyncedToIfds()) {
            return response()->json(['error' => 'Account not synced yet.'], 403);
        }

        $channelName = $request->input('channel_name', '');
        $socketId    = $request->input('socket_id');

        // Only allow private-customer.{id} channels for portal customers
        if (!preg_match('/^private-customer\./', $channelName)) {
            return response()->json(['error' => 'Unauthorized channel.'], 403);
        }

        // Rewrite channel: replace BFF UUID with ifds integer customer ID
        $rewrittenChannel = 'private-customer.' . $customer->ifds_customer_id;

        // Forward auth request to Reverb
        $reverbUrl = sprintf(
            '%s://%s:%s/apps/%s/auth',
            config('services.reverb.scheme', 'http'),
            config('services.reverb.host', '127.0.0.1'),
            config('services.reverb.port', 8080),
            config('services.reverb.app_key'),
        );

        $response = Http::asForm()
            ->timeout(5)
            ->post($reverbUrl, [
                'socket_id'    => $socketId,
                'channel_name' => $rewrittenChannel,
            ]);

        if (!$response->successful()) {
            Log::warning('Reverb auth failed', [
                'status'   => $response->status(),
                'customer' => $customer->id,
                'channel'  => $rewrittenChannel,
            ]);
            return response()->json(['error' => 'WebSocket auth failed.'], 403);
        }

        return response()->json($response->json());
    }
}
