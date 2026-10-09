<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\Attendance;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceKioskService
{
    public function __construct(
        private readonly AttendanceRules $rules
    ) {}

    /**
     * @return array{code: string}
     */
    public function punch(Company $company, string $studentIdCode): array
    {
        $studentIdCode = trim($studentIdCode);
        if ($studentIdCode === '') {
            return ['code' => 'invalid_student_id'];
        }

        $student = Student::query()
            ->where('company_id', $company->id)
            ->where('student_id', $studentIdCode)
            ->first();

        if ($student === null) {
            return ['code' => 'invalid_student_id'];
        }

        $timezone = $this->resolveTimezone($company);
        $now = Carbon::now($timezone);
        $today = $now->format('Y-m-d');
        $time = $now->format('H:i');

        try {
            return DB::transaction(function () use ($company, $student, $today, $time): array {
                $attendance = Attendance::query()
                    ->where('company_id', $company->id)
                    ->where('student_id', $student->id)
                    ->whereDate('date', $today)
                    ->lockForUpdate()
                    ->first();

                if ($attendance === null) {
                    $attributes = [
                        'student_id' => $student->id,
                        'date' => $today,
                        'check_in_time' => $time,
                        'check_out_time' => null,
                    ];

                    $this->rules->validateOrFail($attributes, $company->id);

                    Attendance::query()->create([
                        'company_id' => $company->id,
                        'student_id' => $student->id,
                        'date' => $today,
                        'check_in_time' => $time,
                        'check_out_time' => null,
                    ]);

                    return ['code' => 'checked_in'];
                }

                $existingCheckOut = $this->rules->normalizeTime($attendance->check_out_time);
                if ($existingCheckOut !== null) {
                    return ['code' => 'attendance_complete'];
                }

                $checkIn = $this->rules->normalizeTime($attendance->check_in_time);
                if ($checkIn !== null) {
                    try {
                        $in = Carbon::createFromFormat('H:i', $checkIn);
                        $out = Carbon::createFromFormat('H:i', $time);
                        if (! $out->gt($in)) {
                            return ['code' => 'wait_before_checkout'];
                        }
                    } catch (\Throwable) {
                        // Fall through to full validation.
                    }
                }

                $attributes = [
                    'student_id' => $student->id,
                    'date' => $today,
                    'check_in_time' => $checkIn,
                    'check_out_time' => $time,
                ];

                $this->rules->validateOrFail($attributes, $company->id, $attendance);

                $attendance->check_out_time = $time;
                $attendance->save();

                return ['code' => 'checked_out'];
            });
        } catch (ValidationException) {
            return ['code' => 'unable_to_record'];
        }
    }

    private function resolveTimezone(Company $company): string
    {
        $tz = $company->timezone;
        if (is_string($tz) && trim($tz) !== '') {
            return trim($tz);
        }

        return 'America/New_York';
    }
}
