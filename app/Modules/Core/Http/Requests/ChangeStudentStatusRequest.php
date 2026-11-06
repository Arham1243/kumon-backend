<?php

namespace App\Modules\Core\Http\Requests;

use App\Enums\CompanyTimezone;
use App\Modules\Core\Models\Student;
use Carbon\Carbon;
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
            $companyTz = $student->company?->timezone;
            if ($companyTz === null || $companyTz === '' || ! CompanyTimezone::isValid($companyTz)) {
                $companyTz = (string) config('app.timezone');
            }

            $todayInCompanyTz = Carbon::now($companyTz)->format('Y-m-d');

            $rules = [
                'end_date' => [
                    'required',
                    'date',
                    'date_format:Y-m-d',
                    'before_or_equal:'.$todayInCompanyTz,
                ],
            ];

            if ($student->start_date !== null) {
                $rules['end_date'][] = 'after_or_equal:'.$student->start_date->format('Y-m-d');
            }

            return $rules;
        }

        return [];
    }
}
