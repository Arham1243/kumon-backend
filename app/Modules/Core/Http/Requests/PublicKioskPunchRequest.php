<?php

namespace App\Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicKioskPunchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'string', 'max:64', 'alpha_num'],
        ];
    }
}
