<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\Attendance;
use App\Modules\Core\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AutoCloseOpenAttendanceService
{
    public function __construct(
        private OpenAttendanceQuery $openAttendanceQuery,
        private AttendanceRules $rules
    ) {}

    /**
     * @return array{closed: int, skipped: int, companies: int}
     */
    public function run(): array
    {
        $stats = ['closed' => 0, 'skipped' => 0, 'companies' => 0];

        Company::query()->orderBy('id')->each(function (Company $company) use (&$stats): void {
            $timezone = $this->openAttendanceQuery->resolveTimezone($company);
            $nowLocal = Carbon::now($timezone);
            $windowStart = $nowLocal->copy()->setTime(23, 0, 0);
            $windowEnd = $windowStart->copy()->addMinutes(15);

            if ($nowLocal->lt($windowStart) || $nowLocal->gte($windowEnd)) {
                return;
            }

            $targetDate = $nowLocal->format('Y-m-d');
            $defaultCheckout = '23:00';

            $open = $this->openAttendanceQuery->openAttendancesForCompany($company, $targetDate);
            if ($open->isEmpty()) {
                return;
            }

            $stats['companies']++;

            foreach ($open as $attendance) {
                try {
                    $didClose = DB::transaction(function () use ($attendance, $company, $targetDate, $defaultCheckout): bool {
                        $locked = Attendance::query()
                            ->whereKey($attendance->getKey())
                            ->lockForUpdate()
                            ->first();

                        if ($locked === null || $locked->check_out_time !== null) {
                            return false;
                        }

                        $date = $locked->date?->format('Y-m-d') ?? $targetDate;

                        $checkInNorm = $this->rules->normalizeTime($locked->check_in_time);
                        $checkoutTime = $this->laterTimeOfDay($defaultCheckout, $checkInNorm);

                        $attributes = [
                            'student_id' => (int) $locked->student_id,
                            'date' => $date,
                            'check_in_time' => $checkInNorm,
                            'check_out_time' => $checkoutTime,
                        ];

                        $this->rules->validateOrFail($attributes, $company->id, $locked);

                        $locked->check_out_time = $checkoutTime;
                        $locked->save();

                        return true;
                    });

                    if ($didClose) {
                        $stats['closed']++;
                    } else {
                        $stats['skipped']++;
                    }
                } catch (ValidationException) {
                    $stats['skipped']++;
                } catch (\Throwable) {
                    $stats['skipped']++;
                }
            }
        });

        return $stats;
    }

    private function laterTimeOfDay(string $a, ?string $b): string
    {
        if ($b === null || $b === '') {
            return $a;
        }

        try {
            $ca = Carbon::createFromFormat('H:i', $a);
            $cb = Carbon::createFromFormat('H:i', $b);

            return $cb->gt($ca) ? $cb->format('H:i') : $ca->format('H:i');
        } catch (\Throwable) {
            return $a;
        }
    }
}
