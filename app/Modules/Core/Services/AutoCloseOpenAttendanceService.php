<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\Attendance;
use App\Modules\Core\Models\Company;
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
            $open = $this->openAttendanceQuery->openAttendancesForCompany($company);
            if ($open->isEmpty()) {
                return;
            }

            $stats['companies']++;
            $checkoutTime = $this->openAttendanceQuery->companyLocalTime($company);

            foreach ($open as $attendance) {
                try {
                    $didClose = DB::transaction(function () use ($attendance, $company, $checkoutTime): bool {
                        $locked = Attendance::query()
                            ->whereKey($attendance->getKey())
                            ->lockForUpdate()
                            ->first();

                        if ($locked === null || $locked->check_out_time !== null) {
                            return false;
                        }

                        $date = $locked->date?->format('Y-m-d')
                            ?? $this->openAttendanceQuery->companyLocalToday($company);

                        $attributes = [
                            'student_id' => (int) $locked->student_id,
                            'date' => $date,
                            'check_in_time' => $this->rules->normalizeTime($locked->check_in_time),
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
}
