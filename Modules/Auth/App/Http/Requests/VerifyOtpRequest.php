<?php

namespace Modules\Auth\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'otp'   => ['required', 'string', 'max:6'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->email && !$this->phone) {
                $validator->errors()->add(
                    'identifier',
                    'Email atau phone wajib diisi'
                );
            }
        });
    }
}
