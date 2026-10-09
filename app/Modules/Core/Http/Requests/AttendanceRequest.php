<?php

namespace App\Modules\Core\Http\Requests;

use App\Modules\Core\Models\Attendance;
use App\Modules\Core\Services\AttendanceRules;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Orion\Http\Requests\Request;

class AttendanceRequest extends Request
{
    public function storeRules(): array
    {
        return [
            'student_id' => $this->studentIdRules(),
            'date' => ['required', 'date'],
            'check_in_time' => ['required', 'date_format:H:i'],
            'check_out_time' => ['required', 'date_format:H:i'],
        ];
    }

    public function updateRules(): array
    {
        return [
            'student_id' => ['sometimes', ...$this->studentIdRules()],
            'date' => ['sometimes', 'required', 'date'],
            'check_in_time' => ['sometimes', 'required', 'date_format:H:i'],
            'check_out_time' => ['sometimes', 'required', 'date_format:H:i'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $existing = $this->route('attendance');
            if ($existing !== null && ! $existing instanceof Attendance) {
                $existing = null;
            }

            /** @var AttendanceRules $rules */
            $rules = app(AttendanceRules::class);
            $attributes = $rules->mergeWithExisting($this->all(), $existing);

            foreach ($rules->collectErrors($attributes, $this->user()?->company_id, $existing) as $field => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($field, $message);
                }
            }
        });
    }

    /**
     * @return list<mixed>
     */
    protected function studentIdRules(): array
    {
        $companyId = $this->user()?->company_id;

        $exists = Rule::exists('students', 'id');
        if ($companyId) {
            $exists = $exists->where(fn ($query) => $query->where('company_id', $companyId));
        }

        return ['required', 'integer', $exists];
    }
}
