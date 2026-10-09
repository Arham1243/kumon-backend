<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\Attendance;
use App\Modules\Core\Models\Company;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class OpenAttendanceQuery
{
    public function resolveTimezone(Company $company): string
    {
        $tz = $company->timezone;
        if (is_string($tz) && trim($tz) !== '') {
            return trim($tz);
        }

        return 'America/New_York';
    }

    public function companyLocalToday(Company $company): string
    {
        return Carbon::now($this->resolveTimezone($company))->format('Y-m-d');
    }

    public function companyLocalTime(Company $company): string
    {
        return Carbon::now($this->resolveTimezone($company))->format('H:i');
    }

    /**
     * @return Collection<int, Attendance>
     */
    public function openAttendancesForCompany(Company $company, ?string $date = null): Collection
    {
        $date = $date ?? $this->companyLocalToday($company);

        return Attendance::query()
            ->where('company_id', $company->id)
            ->whereDate('date', $date)
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->with('student')
            ->orderBy('check_in_time')
            ->get();
    }
}
