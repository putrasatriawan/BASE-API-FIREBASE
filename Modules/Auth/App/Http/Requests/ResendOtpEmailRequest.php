<?php

namespace Modules\Auth\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\App\Models\Otp;
use Modules\Auth\App\Models\Profile;

class ResendOtpEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $email = $this->email;

            $profileExists = Profile::whereHas('user', function ($q) use ($email) {
                $q->where('email', $email);
            })->exists();

            if (!$profileExists) {
                $validator->errors()->add(
                    'email',
                    'Email tidak terdaftar'
                );
            }
        });
    }
}
