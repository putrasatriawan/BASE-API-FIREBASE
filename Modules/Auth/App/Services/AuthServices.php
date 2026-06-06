<?php

namespace Modules\Auth\App\Services;

use App\Exceptions\PublicException;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AuthServices
{
    public function resetPassword(string $resetToken, string $newPassword): void
    {
        try {
            $cacheKey = 'reset_password_' . $resetToken;
            $data = Cache::get($cacheKey);

            if (!$data || empty($data['email'])) {
                throw new \Exception('Reset token tidak valid atau kadaluarsa');
            }

            $email = $data['email'];

            $user = User::where('email', $email)->first();

            if (!$user) {
                throw new \Exception('User tidak ditemukan');
            }

            if (!$user->firebase_uid) {
                throw new \Exception('Firebase UID tidak ditemukan');
            }

            $user->update([
                'password' => Hash::make($newPassword),
            ]);

            $auth = Firebase::auth();

            $auth->updateUser($user->firebase_uid, [
                'password' => $newPassword, // plain password (WAJIB)
            ]);

            Cache::forget($cacheKey);

            /**
             * 6️⃣ OPTIONAL: revoke semua session Firebase
             */
            // $auth->revokeRefreshTokens($user->firebase_uid);

        } catch (PublicException $e) {
            Log::error('Reset password by email failed', [
                'reset_token' => $resetToken,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } catch (\Throwable $e) {
            Log::error('Reset password by email failed', [
                'reset_token' => $resetToken,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Gagal reset password: ' . $e->getMessage());
        }
    }
}
