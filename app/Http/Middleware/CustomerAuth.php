<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Facades\JWTAuth;

class CustomerAuth
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
            }

            // Verify token type claim
            $payload = JWTAuth::getPayload();
            if ($payload->get('typ') !== 'customer') {
                return response()->json(['success' => false, 'message' => 'Invalid token type.'], 401);
            }

        } catch (TokenExpiredException) {
            return response()->json(['success' => false, 'message' => 'Token expired.'], 401);
        } catch (JWTException) {
            return response()->json(['success' => false, 'message' => 'Token invalid.'], 401);
        }

        return $next($request);
    }
}
