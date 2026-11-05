<?php

namespace App\Modules\Core\Http\Requests;

use App\Modules\Core\Models\Student;
use Illuminate\Foundation\Http\FormRequest;

class ChangeStudentStatusRequest extends FormRequest
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
        $student = $this->route('student');
        if (! $student instanceof Student) {
            return [];
        }

        if ($student->status) {
            $rules = [
                'end_date' => ['required', 'date', 'date_format:Y-m-d', 'before_or_equal:today'],
            ];

            if ($student->start_date !== null) {
                $rules['end_date'][] = 'after_or_equal:'.$student->start_date->format('Y-m-d');
            }

            return $rules;
        }

        return [];
    }
}
