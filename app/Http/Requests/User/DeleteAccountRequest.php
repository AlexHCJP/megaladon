<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
{
    public function authorize()
    {
        return auth('api')->check();
    }

    public function rules()
    {
        return [
            'password' => ['required', 'string'],
        ];
    }
}
