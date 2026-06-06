<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\ResponseFormatter;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $role)
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

            // Check if user has the required role
            if (!$user->hasRole($role)) {
                return ResponseFormatter::error(
                    null,
                    "Akses ditolak. Anda memerlukan role '{$role}' untuk mengakses fitur ini. Silakan hubungi administrator untuk mendapatkan akses.",
                    403,
                    [
                        'required_role' => $role,
                        'user_roles' => $user->getRoleNames()->toArray()
                    ]
                );
            }

            return $next($request);
        } catch (\Throwable $e) {
            // Log the error for debugging with more details
            \Illuminate\Support\Facades\Log::error('RoleMiddleware error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => $request->user()?->id,
                'role' => $role,
                'request_path' => $request->path(),
                'request_method' => $request->method(),
            ]);

            return ResponseFormatter::error(
                null,
                'Terjadi kesalahan saat memverifikasi role. Silakan coba lagi.',
                500,
                ['middleware_error' => true]
            );
        }
    }
}
