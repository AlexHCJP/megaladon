<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth('api')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'message' => ['required_without:file', 'string'],
            'file' => ['required_without:message', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:10240'],
            'chat_id' => ['required', 'string', 'exists:chats,id'],
        ];
    }
}
