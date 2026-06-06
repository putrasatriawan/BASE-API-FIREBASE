<?php

namespace Modules\Auth\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone' => [
                'required',
                'string',
                'unique:profiles,phone',
                'regex:/^62[0-9]{8,13}$/'
            ],
            'password' => 'required|min:6',
        ];
    }
}
