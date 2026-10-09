<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

/**
 * Single source of truth for attendance business rules (admin UI, future public check-in/out).
 */
class AttendanceRules
{
    /**
     * @param  array{student_id?: int|null, date?: string|null, check_in_time?: string|null, check_out_time?: string|null}  $attributes
     *
     * @throws ValidationException
     */
    public function validateOrFail(array $attributes, ?int $companyId, ?Attendance $except = null): void
    {
        $errors = $this->collectErrors($attributes, $companyId, $except);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array{student_id?: int|null, date?: string|null, check_in_time?: string|null, check_out_time?: string|null}  $attributes
     * @return array<string, list<string>>
     */
    public function collectErrors(array $attributes, ?int $companyId, ?Attendance $except = null): array
    {
        $errors = [];

        $studentId = Arr::get($attributes, 'student_id');
        $date = Arr::get($attributes, 'date');
        $checkIn = $this->normalizeTime(Arr::get($attributes, 'check_in_time'));
        $checkOut = $this->normalizeTime(Arr::get($attributes, 'check_out_time'));

        if ($date !== null && $date !== '') {
            try {
                $attendanceDate = Carbon::parse($date)->startOfDay();
                if ($attendanceDate->gt(Carbon::today())) {
                    $errors['date'][] = 'Date cannot be in the future.';
                }
            } catch (\Throwable) {
                // Format validation is handled by the form request.
            }
        }

        if ($checkIn !== null && $checkOut !== null) {
            try {
                $in = Carbon::createFromFormat('H:i', $checkIn);
                $out = Carbon::createFromFormat('H:i', $checkOut);
                if (! $out->gt($in)) {
                    $errors['check_out_time'][] = 'Check out time must be after check in time.';
                }
            } catch (\Throwable) {
                // Format validation is handled by the form request.
            }
        }

        if ($studentId && $date !== null && $date !== '') {
            $query = Attendance::query()
                ->where('student_id', $studentId)
                ->whereDate('date', $date);

            $resolvedCompanyId = $companyId ?? $except?->company_id;
            if ($resolvedCompanyId) {
                $query->where('company_id', $resolvedCompanyId);
            }

            if ($except !== null) {
                $query->whereKeyNot($except->getKey());
            }

            if ($query->exists()) {
                $errors['date'][] = 'This student already has attendance for this date.';
            }
        }

        return $errors;
    }

    public function normalizeTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $string = is_string($value) ? trim($value) : (string) $value;
        if (strlen($string) >= 5 && preg_match('/^\d{1,2}:\d{2}/', $string) === 1) {
            return substr($string, 0, 5);
        }

        return $string;
    }

    /**
     * @return array{student_id: int|null, date: string|null, check_in_time: string|null, check_out_time: string|null}
     */
    public function mergeWithExisting(array $input, ?Attendance $existing): array
    {
        return [
            'student_id' => isset($input['student_id'])
                ? (int) $input['student_id']
                : ($existing?->student_id !== null ? (int) $existing->student_id : null),
            'date' => $input['date'] ?? $existing?->date?->format('Y-m-d'),
            'check_in_time' => $this->normalizeTime(
                $input['check_in_time'] ?? $existing?->check_in_time
            ),
            'check_out_time' => $this->normalizeTime(
                $input['check_out_time'] ?? $existing?->check_out_time
            ),
        ];
    }
}
