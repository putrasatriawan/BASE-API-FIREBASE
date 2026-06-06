<?php

namespace Modules\Auth\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FirebaseAuthRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'firebase_uid' => 'required|string',
        ];
    }
}
