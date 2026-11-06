<?php

namespace App\Modules\Core\Services;

use App\Modules\Administration\Models\Admin\AdminEmailTemplate;
use App\Modules\Administration\Services\AdminOpenAttendanceMailer;
use App\Modules\Core\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class OpenAttendanceReminderService
{
    public function __construct(
        private OpenAttendanceQuery $openAttendanceQuery,
        private AdminOpenAttendanceMailer $mailer
    ) {}

    /**
     * @return array{companies_messaged: int, emails_sent: int, emails_failed: int, companies_skipped: int}
     */
    public function run(): array
    {
        $stats = [
            'companies_messaged' => 0,
            'emails_sent' => 0,
            'emails_failed' => 0,
            'companies_skipped' => 0,
        ];

        Company::query()->orderBy('id')->each(function (Company $company) use (&$stats): void {
            $timezone = $this->openAttendanceQuery->resolveTimezone($company);
            $nowLocal = Carbon::now($timezone);

            $template = AdminEmailTemplate::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('slug', AdminEmailTemplate::SLUG_OPEN_ATTENDANCE_REMINDER)
                ->where('status', true)
                ->first();

            if ($template === null) {
                $stats['companies_skipped']++;

                return;
            }

            $rawRecipients = $template->to_recipients;
            $toEmails = is_array($rawRecipients)
                ? array_values(array_unique(array_filter(array_map('trim', $rawRecipients), static fn ($e) => $e !== '')))
                : [];

            if ($toEmails === []) {
                $stats['companies_skipped']++;

                return;
            }

            $sendTime = $this->parseDailySendTime($company->daily_summary_report_send_time);
            $localDate = $nowLocal->format('Y-m-d');
            $windowStart = Carbon::parse($localDate.' '.$sendTime, $timezone)->startOfMinute();
            $windowEnd = $windowStart->copy()->addMinutes(15);

            if ($nowLocal->lt($windowStart) || $nowLocal->gte($windowEnd)) {
                $stats['companies_skipped']++;

                return;
            }

            $cacheKey = 'daily-summary-sent:'.$company->id.':'.$localDate;
            $cacheTtl = $nowLocal->copy()->endOfDay()->addSecond();
            if (! Cache::add($cacheKey, true, $cacheTtl)) {
                $stats['companies_skipped']++;

                return;
            }

            $checkInCount = $this->openAttendanceQuery->countCheckInsForCompanyOnDate($company);
            $checkOutCount = $this->openAttendanceQuery->countCheckOutsForCompanyOnDate($company);
            $open = $this->openAttendanceQuery->openAttendancesForCompany($company);

            $emailsOk = 0;
            foreach ($toEmails as $toEmail) {
                if ($this->mailer->sendReminder($toEmail, $company, $open, $checkInCount, $checkOutCount)) {
                    $stats['emails_sent']++;
                    $emailsOk++;
                } else {
                    $stats['emails_failed']++;
                }
            }

            if ($emailsOk > 0) {
                $stats['companies_messaged']++;
            } else {
                Cache::forget($cacheKey);
            }
        });

        return $stats;
    }

    private function parseDailySendTime(mixed $value): string
    {
        $raw = is_string($value) ? trim($value) : '';
        if ($raw === '') {
            return '20:00';
        }

        try {
            return Carbon::createFromFormat('H:i', $raw)->format('H:i');
        } catch (\Throwable) {
            return '20:00';
        }
    }
}
