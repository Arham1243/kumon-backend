<?php

namespace App\Modules\Core\Http\Requests;

use App\Modules\Core\Rules\CardUidRule;
use App\Modules\Core\Support\CardUid;
use Illuminate\Foundation\Http\FormRequest;

class PublicKioskCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('uid')) {
            $this->merge([
                'uid' => CardUid::normalize($this->input('uid')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'uid' => ['required', 'string', new CardUidRule],
        ];
    }
}
