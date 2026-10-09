<?php

namespace App\Modules\Administration\Services;

use App\Modules\Administration\Models\Admin\AdminEmailTemplate;
use App\Modules\Administration\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Services\OpenAttendanceTableHtml;
use App\Services\MailService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AdminOpenAttendanceMailer
{
    public function __construct(
        private AdminEmailTemplateRenderer $renderer,
        private OpenAttendanceTableHtml $tableHtml
    ) {}

    /**
     * @param  Collection<int, \App\Modules\Core\Models\Attendance>  $openAttendances
     */
    public function sendReminder(User $recipient, Company $company, Collection $openAttendances): bool
    {
        $template = AdminEmailTemplate::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('slug', AdminEmailTemplate::SLUG_OPEN_ATTENDANCE_REMINDER)
            ->where('status', true)
            ->first();

        if ($template === null) {
            return false;
        }

        $name = filled($recipient->name) ? (string) $recipient->name : 'there';
        $table = $this->tableHtml->render($openAttendances, $company);

        $built = $this->renderer->buildOpenAttendanceReminderEmail([
            'intro_html' => $template->intro_html,
            'body_html' => $template->body_html,
            'footer_html' => $template->footer_html,
            'subject_template' => $template->subject_template,
        ], [
            'name' => $name,
            'year' => now()->format('Y'),
            'attendance_table' => $table,
        ]);

        try {
            $result = MailService::sendEmail([
                'company' => MailService::systemMailgunCompany(),
                'to' => (string) $recipient->email,
                'subject' => $built['subject'],
                'html' => $built['html'],
                'template' => $template,
                'user_id' => $recipient->id,
                'entity_type' => MailService::ENTITY_SYSTEM,
            ]);

            if (! $result['success']) {
                Log::error('Open attendance reminder failed for '.$recipient->email.': '.($result['message'] ?? ''));

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Open attendance reminder failed for '.$recipient->email.': '.$e->getMessage());

            return false;
        }
    }
}
