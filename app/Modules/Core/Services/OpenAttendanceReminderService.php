<?php

namespace App\Modules\Core\Services;

use App\Modules\Administration\Models\Admin\AdminEmailTemplate;
use App\Modules\Administration\Services\AdminOpenAttendanceMailer;
use App\Modules\Core\Models\Company;

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

            $checkInCount = $this->openAttendanceQuery->countCheckInsForCompanyOnDate($company);
            $checkOutCount = $this->openAttendanceQuery->countCheckOutsForCompanyOnDate($company);
            $open = $this->openAttendanceQuery->openAttendancesForCompany($company);

            $stats['companies_messaged']++;
            foreach ($toEmails as $toEmail) {
                if ($this->mailer->sendReminder($toEmail, $company, $open, $checkInCount, $checkOutCount)) {
                    $stats['emails_sent']++;
                } else {
                    $stats['emails_failed']++;
                }
            }
        });

        return $stats;
    }
}
