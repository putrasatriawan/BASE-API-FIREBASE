<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\ResponseFormatter;

class DebugApiMiddleware
{
    /**
     * Handle an incoming request for debugging API issues
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Log request details for debugging
            \Log::info('API Request Debug', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'headers' => $request->headers->all(),
                'user_id' => $request->user()?->id,
                'user_email' => $request->user()?->email,
                'user_roles' => $request->user()?->getRoleNames()->toArray() ?? [],
                'ip' => $request->ip(),
            ]);

            $response = $next($request);

            // Log response for debugging
            \Log::info('API Response Debug', [
                'status' => $response->getStatusCode(),
                'url' => $request->fullUrl(),
            ]);

            return $response;
        } catch (\Throwable $e) {
            \Log::error('DebugApiMiddleware caught exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'url' => $request->fullUrl(),
                'user_id' => $request->user()?->id,
            ]);

            return ResponseFormatter::error(
                null,
                'Terjadi kesalahan pada server. Silakan coba lagi atau hubungi administrator.',
                500,
                [
                    'debug_info' => config('app.debug') ? [
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ] : null
                ]
            );
        }
    }
}
