<?php

namespace Modules\Auth\App\Http\Controllers;

use App\Http\ResponseFormatter;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Auth\App\Services\FirebaseAuthService;
use Modules\Auth\App\Services\OtpService;
use Modules\Auth\App\Services\AuthServices;
use Modules\Auth\App\Http\Requests\FirebaseAuthRequest;
use Modules\Auth\App\Http\Requests\RegisterRequest;
use Modules\Auth\App\Http\Requests\VerifyOtpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Auth\App\Http\Requests\ResendOtpEmailRequest;
use Modules\Auth\App\Http\Requests\ResendOtpWaRequest;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        protected FirebaseAuthService $firebaseService,
        protected OtpService $otpService,
        protected AuthServices $authServices
    ) {}

    /**
     * REGISTER (Firebase)
     */

    public function register(RegisterRequest $request)
    {
        try {
            $user = $this->firebaseService->registerManual(
                $request->validated()
            );

            $token = $user->createToken('api-sekolahin')->plainTextToken;

            return ResponseFormatter::success([
                'token' => $token,
                'user' => [
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                ],
                'email_verified_at' => $user->email_verified_at,
            ], 'Register success');
        } catch (\Throwable $e) {
            Log::error('Register failed', [
                'payload' => $request->validated(),
                'error'   => $e->getMessage(),
            ]);

            return ResponseFormatter::error(
                null,
                'Register gagal: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * LOGIN (Firebase)
     */
    public function firebase(FirebaseAuthRequest $request)
    {
        try {
            $user = $this->firebaseService->verifyAndSyncUser($request->firebase_uid);

            // Multi-device support: Allow multiple active sessions
            // Each device gets its own token with device info
            $deviceInfo = $request->header('User-Agent', 'Unknown Device');
            $tokenName = 'api-sekolahin-' . substr(md5($deviceInfo . now()), 0, 8);

            $tokenExpiration = now()->addDays(7); // 7 days expiration
            $token = $user->createToken($tokenName, ['*'], $tokenExpiration)->plainTextToken;

            // Load profile relationship
            $user->load('profile');

            $data = [
                'token' => $token,
                'expires_at' => $tokenExpiration->toISOString(),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'roles' => $user->getRoleNames(),
                ],
                'profile' => $user->profile ? [
                    'full_name' => $user->profile->full_name,
                    'username' => $user->profile->username,
                    'phone' => $user->profile->phone,
                    'avatar' => $user->profile->avatar,
                    'birth_date' => $user->profile->birth_date,
                    'gender' => $user->profile->gender,
                ] : null,
                'session_info' => [
                    'device' => $deviceInfo,
                    'created_at' => now()->toISOString(),
                    'expires_at' => $tokenExpiration->toISOString(),
                ]
            ];

            return ResponseFormatter::success($data, 'Login success');
        } catch (\Throwable $e) {
            Log::error('Firebase login failed', [
                'firebase_uid' => $request->firebase_uid,
                'error' => $e->getMessage(),
            ]);

            return ResponseFormatter::error(
                null,
                'Login gagal: ' . $e->getMessage(),
                500
            );
        }
    }
    /**
     * LOGIN (Firebase Admin)
     */
    public function firebaseAdmin(FirebaseAuthRequest $request)
    {
        try {
            $user = $this->firebaseService->verifyAndSyncUser($request->firebase_uid);

            if (!$user->hasRole('admin')) {
                return ResponseFormatter::error(
                    null,
                    'Unauthorized. Admin access only.',
                    403
                );
            }

            // For admin, enforce single session for security
            $user->tokens()->delete();

            $tokenExpiration = now()->addDays(1); // 1 day for admin
            $token = $user->createToken('api-sekolahin-admin', ['*'], $tokenExpiration)->plainTextToken;

            // Load profile relationship
            $user->load('profile');

            $data = [
                'token' => $token,
                'expires_at' => $tokenExpiration->toISOString(),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                ],
                'profile' => $user->profile ? [
                    'full_name' => $user->profile->full_name,
                    'username' => $user->profile->username,
                    'phone' => $user->profile->phone,
                    'avatar' => $user->profile->avatar,
                    'birth_date' => $user->profile->birth_date,
                    'gender' => $user->profile->gender,
                ] : null,
            ];

            return ResponseFormatter::success($data, 'Login success');
        } catch (\Throwable $e) {
            Log::error('Firebase admin login failed', [
                'firebase_uid' => $request->firebase_uid,
                'error' => $e->getMessage(),
            ]);

            return ResponseFormatter::error(
                null,
                'Login gagal: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * LOGOUT
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        $data = [
            'status' => true,
            'message' => 'Logout success',
        ];
        return ResponseFormatter::success($data, 'Logout success');
    }

    /**
     * ME
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load('profile');

        $data = [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'token_expires_at' => $request->user()->currentAccessToken()->expires_at?->timezone('Asia/Jakarta')->toDateTimeString(),
            'profile' => $user->profile ? [
                'full_name' => $user->profile->full_name,
                'username' => $user->profile->username,
                'phone' => $user->profile->phone,
                'avatar' => $user->profile->avatar_url,
                'birth_date' => $user->profile->birth_date,
                'gender' => $user->profile->gender,
            ] : null,
            'addresses' => $user->address ? $user->address->map(function ($address) {
                return [
                    'id' => $address->id,
                    'label' => $address->label,
                    'receiver_name' => $address->receiver_name,
                    'receiver_phone' => $address->receiver_phone,
                    'biteship_id' => $address->biteship_id,
                    'name' => $address->name,
                    'country' => $address->country,
                    'country_code' => $address->country_code,
                    'province' => $address->province,
                    'city' => $address->city,
                    'district' => $address->district,
                    'lat' => $address->lat,
                    'lon' => $address->lon,
                    'postal_code' => $address->postal_code,
                ];
            }) : [],
        ];
        return ResponseFormatter::success($data, 'User data retrieved');
    }

    /**
     * Get all active sessions with device info
     */
    public function sessions(Request $request)
    {
        $user = $request->user();
        $currentTokenId = $request->user()->currentAccessToken()->id;

        $sessions = $user->tokens()
            ->select('id', 'name', 'last_used_at', 'created_at', 'expires_at')
            ->get()
            ->map(function ($token) use ($currentTokenId) {
                // Extract device info from token name
                $deviceInfo = 'Unknown Device';
                if (str_contains($token->name, 'api-sekolahin-')) {
                    $deviceInfo = 'Device ' . substr($token->name, -8);
                }

                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'device_info' => $deviceInfo,
                    'is_current' => $token->id === $currentTokenId,
                    'last_used_at' => $token->last_used_at?->toISOString(),
                    'created_at' => $token->created_at->toISOString(),
                    'expires_at' => $token->expires_at?->toISOString(),
                    'status' => $token->expires_at && $token->expires_at->isPast() ? 'expired' : 'active',
                ];
            });

        return ResponseFormatter::success($sessions, 'Sessions retrieved');
    }

    /**
     * Revoke specific session
     */
    public function revokeSession(Request $request, $tokenId)
    {
        $user = $request->user();
        $currentTokenId = $request->user()->currentAccessToken()->id;

        if ($tokenId == $currentTokenId) {
            return ResponseFormatter::error(
                null,
                'Cannot revoke current session. Use logout instead.',
                400
            );
        }

        $deleted = $user->tokens()->where('id', $tokenId)->delete();

        if (!$deleted) {
            return ResponseFormatter::error(
                null,
                'Session not found',
                404
            );
        }

        return ResponseFormatter::success(null, 'Session revoked successfully');
    }

    /**
     * Revoke all other sessions except current
     */
    public function revokeOtherSessions(Request $request)
    {
        $user = $request->user();
        $currentTokenId = $request->user()->currentAccessToken()->id;

        $deletedCount = $user->tokens()
            ->where('id', '!=', $currentTokenId)
            ->delete();

        return ResponseFormatter::success(
            ['revoked_sessions' => $deletedCount],
            'Other sessions revoked successfully'
        );
    }

    /**
     * Refresh current token (extend expiration)
     */
    public function refreshToken(Request $request)
    {
        try {
            $user = $request->user();
            $currentToken = $request->user()->currentAccessToken();

            // Create new token with same device info
            $deviceInfo = $request->header('User-Agent', 'Unknown Device');
            $tokenName = 'api-sekolahin-' . substr(md5($deviceInfo . now()), 0, 8);

            $tokenExpiration = now()->addDays(7);
            $newToken = $user->createToken($tokenName, ['*'], $tokenExpiration)->plainTextToken;

            // Delete old token
            $currentToken->delete();

            $data = [
                'token' => $newToken,
                'expires_at' => $tokenExpiration->toISOString(),
                'refreshed_at' => now()->toISOString(),
            ];

            return ResponseFormatter::success($data, 'Token refreshed successfully');
        } catch (\Throwable $e) {
            Log::error('Token refresh failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return ResponseFormatter::error(
                null,
                'Token refresh failed: ' . $e->getMessage(),
                500
            );
        }
    }

    public function resendOtpWa(ResendOtpWaRequest $request)
    {
        try {
            $this->otpService->resendOtpWa($request->phone);

            return ResponseFormatter::success(
                null,
                'OTP berhasil dikirim ulang ke WhatsApp'
            );
        } catch (\Throwable $e) {
            return ResponseFormatter::error(
                null,
                'Gagal mengirim OTP WhatsApp: ' . $e->getMessage(),
                500
            );
        }
    }

    public function resendOtpEmail(ResendOtpEmailRequest $request)
    {
        try {
            $this->otpService->resendOtpEmail($request->email);

            return ResponseFormatter::success(
                null,
                'OTP berhasil dikirim ulang ke email'
            );
        } catch (\Throwable $e) {
            return ResponseFormatter::error(
                null,
                'Gagal mengirim OTP email: ' . $e->getMessage(),
                500
            );
        }
    }

    public function verifyOtpRegister(VerifyOtpRequest $request)
    {
        // dd($request);
        try {
            $result = $this->otpService->verifyOtpRegister(
                $request->email,
                $request->phone,
                $request->otp
            );

            return ResponseFormatter::success(
                $result,
                'OTP berhasil diverifikasi'
            );
        } catch (\Throwable $e) {
            return ResponseFormatter::error(
                null,
                'Gagal verifikasi OTP: ' . $e->getMessage(),
                500
            );
        }
    }

    public function forgotPassword(Request $request)
    {
        try {
            $this->otpService->createForgotPasswordOtp(
                $request->email,
                $request->phone
            );

            return ResponseFormatter::success(
                null,
                'OTP berhasil dikirim'
            );
        } catch (\Throwable $e) {
            Log::error('Forgot password failed', [
                'email' => $request->email,
                'phone' => $request->phone,
                'error' => $e->getMessage(),
            ]);

            return ResponseFormatter::error(
                null,
                'Gagal mengirim OTP: ' . $e->getMessage(),
                500
            );
        }
    }

    public function verifyForgotPasswordOtp(Request $request)
    {
        try {
            $token = $this->otpService->verifyForgotPasswordOtp(
                $request->email,
                $request->phone,
                $request->otp
            );

            return ResponseFormatter::success(
                ['reset_token' => $token],
                'OTP valid'
            );
        } catch (\Throwable $e) {
            Log::error('Verify forgot password OTP failed', [
                'email' => $request->email,
                'phone' => $request->phone,
                'otp'   => $request->otp,
                'error' => $e->getMessage(),
            ]);

            return ResponseFormatter::error(
                null,
                'Gagal verifikasi OTP: ' . $e->getMessage(),
                500
            );
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $this->authServices->resetPassword(
                $request->reset_token,
                $request->password
            );

            return ResponseFormatter::success(
                null,
                'Password berhasil diubah'
            );
        } catch (\Throwable $e) {
            Log::error('Reset password controller failed', [
                'error' => $e->getMessage(),
            ]);

            return ResponseFormatter::error(
                null,
                'Gagal reset password: ' . $e->getMessage(),
                500
            );
        }
    }
}
