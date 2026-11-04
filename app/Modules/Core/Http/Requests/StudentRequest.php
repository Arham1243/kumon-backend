<?php

namespace App\Modules\Core\Http\Requests;

use App\Modules\Core\Models\Student;
use App\Modules\Core\Rules\CardUidRule;
use App\Modules\Core\Rules\UniqueCompanyCardUid;
use Illuminate\Validation\Rule;
use Orion\Http\Requests\Request;

class StudentRequest extends Request
{
    public function storeRules(): array
    {
        return [
            'student_id' => $this->studentIdRules(),
            'name' => ['required', 'string', 'max:255'],
            'card_uid' => $this->cardUidRules(),
        ];
    }

    public function updateRules(): array
    {
        return [
            'student_id' => ['sometimes', ...$this->studentIdRules()],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'card_uid' => ['sometimes', 'nullable', ...array_slice($this->cardUidRules(), 1)],
        ];
    }

    /**
     * @return list<mixed>
     */
    protected function studentIdRules(): array
    {
        $companyId = $this->user()?->company_id;

        $unique = Rule::unique('students', 'student_id');
        if ($companyId) {
            $unique = $unique->where(fn ($query) => $query->where('company_id', $companyId));
        }

        if ($this->route('student')) {
            $unique = $unique->ignore($this->route('student'), 'uuid');
        }

        return ['bail', 'required', 'integer', 'min:1', $unique];
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

    public function commonMessages(): array
    {
        return [
            'student_id.required' => 'Student ID is required.',
            'student_id.integer' => 'Student ID must be an integer.',
            'student_id.min' => 'Student ID must be at least 1.',
        ];
    }
}
