<?php

namespace App\Modules\Administration\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                'string',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&]).+$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'The password must include upper and lower case letters, a number, and one symbol from @$!%*#?&.',
        ];
    }
}
