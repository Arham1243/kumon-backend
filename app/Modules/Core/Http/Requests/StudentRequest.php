<?php

namespace App\Modules\Core\Http\Requests;

use App\Modules\Core\Models\Student;
use App\Modules\Core\Rules\CardUidRule;
use App\Modules\Core\Rules\UniqueCompanyCardUid;
use App\Modules\Core\Support\CardUid;
use Orion\Http\Requests\Request;

class StudentRequest extends Request
{
    public function storeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_1_mobile' => ['nullable', 'string', 'max:32'],
            'parent_2_mobile' => ['nullable', 'string', 'max:32'],
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'maths' => ['sometimes', 'boolean'],
            'reading' => ['sometimes', 'boolean'],
            'tuition_fee' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'card_uid' => $this->cardUidRules(),
        ];
    }

    public function updateRules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'parent_1_mobile' => ['sometimes', 'nullable', 'string', 'max:32'],
            'parent_2_mobile' => ['sometimes', 'nullable', 'string', 'max:32'],
            'start_date' => ['sometimes', 'required', 'date', 'date_format:Y-m-d'],
            'maths' => ['sometimes', 'boolean'],
            'reading' => ['sometimes', 'boolean'],
            'tuition_fee' => ['sometimes', 'nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'card_uid' => ['sometimes', 'nullable', ...array_slice($this->cardUidRules(), 1)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('maths')) {
            $merge['maths'] = filter_var($this->input('maths'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }

        if ($this->has('reading')) {
            $merge['reading'] = filter_var($this->input('reading'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }

        if ($this->has('card_uid')) {
            $normalized = CardUid::normalize($this->input('card_uid'));
            $merge['card_uid'] = $normalized;
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return list<mixed>
     */
    protected function cardUidRules(): array
    {
        $student = $this->route('student');
        $ignore = $student instanceof Student
            ? $student
            : (is_string($student) ? $student : null);

        return [
            'nullable',
            'bail',
            new CardUidRule,
            new UniqueCompanyCardUid($ignore),
        ];
    }
}
