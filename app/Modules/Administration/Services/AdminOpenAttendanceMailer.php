<?php

namespace App\Modules\Administration\Services;

use App\Modules\Administration\Models\Admin\AdminEmailTemplate;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Services\OpenAttendanceQuery;
use App\Modules\Core\Services\OpenAttendanceSummaryCardsHtml;
use App\Modules\Core\Services\OpenAttendanceTableHtml;
use App\Services\MailService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AdminOpenAttendanceMailer
{
    public function __construct(
        private AdminEmailTemplateRenderer $renderer,
        private OpenAttendanceTableHtml $tableHtml,
        private OpenAttendanceSummaryCardsHtml $summaryCardsHtml,
        private OpenAttendanceQuery $openAttendanceQuery
    ) {}

    /**
     * @param  Collection<int, \App\Modules\Core\Models\Attendance>  $openAttendances
     */
    public function sendReminder(string $toEmail, Company $company, Collection $openAttendances, int $checkInCount, int $checkOutCount): bool
    {
        $template = AdminEmailTemplate::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('slug', AdminEmailTemplate::SLUG_OPEN_ATTENDANCE_REMINDER)
            ->where('status', true)
            ->first();

        if ($template === null) {
            return false;
        }

        $reportDate = format_date($this->openAttendanceQuery->companyLocalToday($company), null, $company);
        $notCheckedOutCount = $openAttendances->count();
        $cards = $this->summaryCardsHtml->render($checkInCount, $checkOutCount, $notCheckedOutCount);
        $table = $this->tableHtml->render($openAttendances, $company);

        $built = $this->renderer->buildOpenAttendanceReminderEmail([
            'intro_html' => $template->intro_html,
            'body_html' => $template->body_html,
            'footer_html' => $template->footer_html,
            'subject_template' => $template->subject_template,
        ], [
            'year' => now()->format('Y'),
            'check_in_count' => (string) $checkInCount,
            'check_out_count' => (string) $checkOutCount,
            'not_checked_out_count' => (string) $notCheckedOutCount,
            'attendance_summary_cards' => $cards,
            'report_date' => $reportDate,
            'attendance_table' => $table,
        ]);

        try {
            $result = MailService::sendEmail([
                'company' => MailService::systemMailgunCompany(),
                'to' => $toEmail,
                'subject' => $built['subject'],
                'html' => $built['html'],
                'template' => $template,
                'user_id' => null,
                'entity_type' => MailService::ENTITY_SYSTEM,
            ]);

            if (! $result['success']) {
                Log::error('Open attendance reminder failed for '.$toEmail.': '.($result['message'] ?? ''));

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Open attendance reminder failed for '.$toEmail.': '.$e->getMessage());

            return false;
        }
    }
}
