<?php

namespace App\Modules\Core\Services;

use App\Modules\Administration\Models\User;
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
            $open = $this->openAttendanceQuery->openAttendancesForCompany($company);
            if ($open->isEmpty()) {
                $stats['companies_skipped']++;

                return;
            }

            $recipients = User::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('status', 'active')
                ->whereNotNull('email')
                ->whereHas('role', fn ($query) => $query->where('is_super_admin', true))
                ->get();

            if ($recipients->isEmpty()) {
                $stats['companies_skipped']++;

                return;
            }

            $stats['companies_messaged']++;
            foreach ($recipients as $recipient) {
                if ($this->mailer->sendReminder($recipient, $company, $open)) {
                    $stats['emails_sent']++;
                } else {
                    $stats['emails_failed']++;
                }
            }
        });

        return $stats;
    }
}
