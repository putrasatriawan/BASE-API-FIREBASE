<?php

namespace Modules\Auth\App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\Auth\App\Models\Otp;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\App\Models\Profile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class OtpService
{
    /**
     * Generate random numeric OTP
     */
    protected function generateOtp(int $length = 6): string
    {
        return str_pad(
            random_int(0, pow(10, $length) - 1),
            $length,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Create OTP for register
     */
    public function createRegisterOtp(?string $phone, ?string $email): Otp
    {
        Otp::where('phone', $phone)
            ->orWhere('email', $email)
            ->delete();

        $otp = $this->generateOtp();

        return Otp::create([
            'phone' => $phone,
            'email' => $email,
            'otp' => $otp,
            'expired_at' => now()->addMinutes(5),

            'purpose' => 'register',
        ]);
    }

    /**
     * SEND OTP VIA WA
     */
    public function sendOtpViaWa(string $phone, string $otp): void
    {
        $message = "🔐 *Kode OTP XORIX*\n\n"
            . "Kode verifikasi kamu adalah:\n"
            . "*{$otp}*\n\n"
            . "⏳ Berlaku selama 5 menit.\n"
            . "Jangan bagikan kode ini ke siapapun.";

        try {
            $fonteUrl = config('auth.fonnte.url');
            $fonteToken = config('auth.fonnte.token');

            if (!$fonteUrl || !$fonteToken) {
                Log::error('Fonnte configuration missing', [
                    'phone' => $phone,
                ]);
                return;
            }

            $response = Http::withHeaders([
                'Authorization' => $fonteToken,
            ])
                ->timeout(10)
                ->post($fonteUrl, [
                    'target'  => $phone, // format 62xxxx
                    'message' => $message,
                ]);

            if (!$response->successful()) {
                Log::error('Send OTP WA failed', [
                    'phone' => $phone,
                    'response' => $response->body(),
                ]);
            }
        } catch (Throwable $e) {
            Log::error('Send OTP WA exception', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }
    public function resendOtpWa(?string $phone): Otp
    {
        try {
            if (!$phone) {
                throw new \InvalidArgumentException('Phone is required');
            }

            $otpData = Otp::where('purpose', 'register')
                ->where('phone', $phone)
                ->latest()
                ->first();

            if (!$otpData) {
                throw new \Exception('Nomor WA Tidak Valid');
            }

            if ($otpData && !$otpData->isExpired()) {
                $otp = $otpData->otp;
            } else {
                Otp::where('phone', $phone)->delete();

                $otp = $this->generateOtp();

                $otpData = Otp::create([
                    'phone' => $phone,
                    'email' => null,
                    'otp' => $otp,
                    'expired_at' => now()->addMinutes(5),

                    'purpose' => 'register',
                ]);
            }

            $this->sendOtpViaWa($phone, $otp);

            return $otpData;
        } catch (Throwable $e) {
            Log::error('Resend OTP WA failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            // lempar ulang agar controller yang handle response
            throw $e;
        }
    }
    public function resendOtpEmail(?string $email): Otp
    {
        try {
            if (!$email) {
                throw new \InvalidArgumentException('Email is required');
            }

            $otpData = Otp::where('purpose', 'register')
                ->where('email', $email)
                ->latest()
                ->first();

            if ($otpData && !$otpData->isExpired()) {
                $otp = $otpData->otp;
            } else {
                Otp::where('email', $email)->delete();

                $otp = $this->generateOtp();

                $otpData = Otp::create([
                    'phone' => null,
                    'email' => $email,
                    'otp' => $otp,
                    'expired_at' => now()->addMinutes(5),

                    'purpose' => 'register',
                ]);
            }

            $this->sendOtpViaEmail($email, $otp);

            return $otpData;
        } catch (Throwable $e) {
            Log::error('Resend OTP Email failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
    public function sendOtpViaEmail(string $email, string $otp): void
    {
        $subject = 'Kode OTP Verifikasi Akun';
        $message = <<<EOT
        Kode OTP kamu adalah:

        $otp

        Kode ini berlaku selama 5 menit.
        Jangan bagikan kode ini kepada siapapun.

        Jika kamu tidak merasa melakukan permintaan ini, abaikan email ini.
        EOT;

        try {
            Mail::raw($message, function ($mail) use ($email, $subject) {
                $mail->to($email)
                    ->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::error('Send OTP Email failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }



    public function verifyOtpRegister(?string $email, ?string $phone, string $otp): array
    {
        try {
            $profileQuery = Profile::query()->with('user');

            if ($email) {
                $profileQuery->whereHas('user', function ($q) use ($email) {
                    $q->where('email', $email);
                });
            }

            if ($phone) {
                $profileQuery->where('phone', $phone);
            }

            $profile = $profileQuery->first();

            if (!$profile) {
                throw new \Exception('Profile tidak ditemukan');
            }

            $otpQuery = Otp::where('otp', $otp)
                ->where('purpose', 'register');

            if ($email) {
                $otpQuery->where('email', $profile->user->email);
            }

            if ($phone) {
                $otpQuery->where('phone', $profile->phone);
            }

            // dd($profile->phone);
            $otpData = $otpQuery->latest()->first();

            if (!$otpData) {
                throw new \Exception('OTP tidak valid');
            }

            if ($otpData->isExpired()) {
                throw new \Exception('OTP sudah kedaluwarsa');
            }

            User::where(function ($q) use ($email, $phone) {
                if ($email) {
                    $q->where('email', $email);
                }

                if ($phone) {
                    $q->orWhereHas('profile', function ($p) use ($phone) {
                        $p->where('phone', $phone);
                    });
                }
            })
                ->whereNull('email_verified_at')
                ->update([
                    'email_verified_at' => now(),
                ]);
            if ($phone && empty($profile->phone_verified_at)) {
                $profile->update([
                    'email_verified_at' => now(),
                ]);
            }

            Otp::where('purpose', 'register')
                ->where(function ($q) use ($profile) {
                    $q->where('email', $profile->user->email)
                        ->orWhere('phone', $profile->phone);
                })
                ->delete();

            return [
                'user_id' => $profile->user->id,
                'email'   => $profile->user->email,
                'phone'   => $profile->phone,
                'email_verified_at' => now(),
            ];
        } catch (Throwable $e) {
            Log::error('Verify OTP Register failed', [
                'email' => $email,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
    public function createForgotPasswordOtp(?string $email, ?string $phone): Otp
    {
        try {
            if (!$email && !$phone) {
                throw new \Exception('Email atau phone wajib diisi');
            }

            $userQuery = User::query()->with('profile');

            if ($email) {
                $userQuery->where('email', $email);
            }

            if ($phone) {
                $userQuery->whereHas('profile', fn($q) => $q->where('phone', $phone));
            }

            $user = $userQuery->first();

            if (!$user) {
                throw new \Exception('User tidak ditemukan');
            }

            // hapus OTP lama
            Otp::where('purpose', 'forgot_password')
                ->where(function ($q) use ($user) {
                    $q->where('email', $user->email)
                        ->orWhere('phone', optional($user->profile)->phone);
                })
                ->delete();

            $otp = $this->generateOtp();

            $otpData = Otp::create([
                'email'      => $email ? $user->email : null,
                'phone'      => $phone ? $user->profile->phone : null,
                'otp'        => $otp,
                'purpose'    => 'forgot_password',
                'expired_at' => now()->addMinutes(5),

            ]);

            if ($email) {
                $this->sendOtpViaEmail($user->email, $otp);
            }

            if ($phone) {
                $this->sendOtpViaWa($user->profile->phone, $otp);
            }

            return $otpData;
        } catch (Throwable $e) {
            Log::error('Create forgot password OTP failed', [
                'email' => $email,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    public function verifyForgotPasswordOtp(?string $email, ?string $phone, string $otp): string
    {
        try {
            $otpQuery = Otp::where('otp', $otp)
                ->where('purpose', 'forgot_password');

            if ($email) {
                $otpQuery->where('email', $email);
            }

            if ($phone) {
                $otpQuery->where('phone', $phone);
            }

            $otpData = $otpQuery->latest()->first();

            if (!$otpData) {
                throw new \Exception('OTP tidak valid');
            }

            if ($otpData->isExpired()) {
                throw new \Exception('OTP sudah kedaluwarsa');
            }

            // generate reset token (simple & aman)
            $resetToken = Str::uuid()->toString();

            Cache::put(
                'reset_password_' . $resetToken,
                [
                    'email' => $otpData->email,
                    'phone' => $otpData->phone,
                ],
                now()->addMinutes(10)
            );

            // hapus OTP
            $otpData->delete();

            return $resetToken;
        } catch (Throwable $e) {
            Log::error('Verify forgot password OTP failed', [
                'email' => $email,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
