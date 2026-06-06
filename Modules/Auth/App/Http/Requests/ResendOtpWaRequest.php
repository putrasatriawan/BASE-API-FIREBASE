<?php

namespace Modules\Auth\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\App\Models\Otp;
use Modules\Auth\App\Models\Profile;

class ResendOtpWaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $phone = $this->phone;

            $profileExists = Profile::where('phone', $phone)->exists();

            if (!$profileExists) {
                $validator->errors()->add(
                    'phone',
                    'Nomor WhatsApp tidak terdaftar'
                );
            }

            $otpExists = Otp::where('phone', $phone)
                ->where('purpose', 'register')
                ->exists();

            if (!$otpExists) {
                $validator->errors()->add(
                    'phone',
                    'OTP belum pernah dibuat untuk nomor ini'
                );
            }
        });
    }
}
