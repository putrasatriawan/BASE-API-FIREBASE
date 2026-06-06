<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\ResponseFormatter;
use Laravel\Sanctum\PersonalAccessToken;

class HandleExpiredTokens
{
    public function handle(Request $request, Closure $next)
    {
        // Check if request has Authorization header
        if ($request->hasHeader('Authorization')) {
            $token = $request->bearerToken();

            if ($token) {
                $accessToken = PersonalAccessToken::findToken($token);

                if ($accessToken && $accessToken->expires_at && $accessToken->expires_at->isPast()) {
                    // Token is expired, delete it
                    $accessToken->delete();

                    return ResponseFormatter::error(
                        null,
                        'Token expired. Please login again.',
                        401
                    );
                }
            }
        }

        return $next($request);
    }
}
