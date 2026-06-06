<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\ResponseFormatter;

class EnsureEmailVerifiedApi
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return ResponseFormatter::error(
                    null,
                    'Anda harus login terlebih dahulu untuk mengakses fitur ini.',
                    401,
                    ['authentication_required' => true]
                );
            }

            if (is_null($user->email_verified_at)) {
                return ResponseFormatter::error(
                    null,
                    'Email / Whatsapp Anda belum diverifikasi. Silakan verifikasi email terlebih dahulu untuk mengakses fitur ini.',
                    403,
                    [
                        'email' => $user->email,
                        'verification_required' => true
                    ]
                );
            }

            return $next($request);
        } catch (\Throwable $e) {
            // Log the error for debugging
            \Illuminate\Support\Facades\Log::error('EnsureEmailVerifiedApi error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id
            ]);

            return ResponseFormatter::error(
                null,
                'Terjadi kesalahan saat memverifikasi email. Silakan coba lagi.',
                500,
                ['middleware_error' => true]
            );
        }
    }
}
