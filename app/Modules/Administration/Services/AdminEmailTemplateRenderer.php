<?php

namespace App\Modules\Administration\Services;

use App\Modules\Administration\Models\Admin\AdminEmailTemplate;
use App\Modules\Administration\Models\UserLoginOtp;
use App\Modules\Core\Services\OpenAttendanceSummaryCardsHtml;
use App\Modules\Core\Services\OpenAttendanceTableHtml;

/**
 * Resolves HTML for outbound emails driven by {@see AdminEmailTemplate} rows.
 */
class AdminEmailTemplateRenderer
{
    public function interpolate(?string $html, array $vars): string
    {
        if ($html === null) {
            return '';
        }
        $out = $html;
        foreach ($vars as $key => $value) {
            if (! is_string($key) && ! is_int($key)) {
                continue;
            }
            $key = (string) $key;
            if ($key === '') {
                continue;
            }
            $replacement = (string) $value;
            $pattern = '/\{\{\s*'.preg_quote($key, '/').'\s*\}\}/u';
            $replaced = preg_replace($pattern, $replacement, $out);
            $out = $replaced ?? $out;
        }

        return $out;
    }

    public function normalizeMailSubject(string $subject): string
    {
        $subject = trim($subject);
        $subject = preg_replace('/\s*-\s*$/', '', $subject) ?? $subject;
        $subject = preg_replace('/:\s*$/', '', $subject) ?? $subject;

        if (strlen($subject) > 200) {
            $subject = substr($subject, 0, 197).'...';
        }

        return $subject;
    }

    /**
     * @return array{html: string, subject: string}
     */
    public function buildPreview(string $slug, array $fieldOverrides = [], array $placeholderOverrides = []): array
    {
        if ($slug === AdminEmailTemplate::SLUG_OPEN_ATTENDANCE_REMINDER) {
            return $this->buildOpenAttendanceReminderEmail($fieldOverrides, $placeholderOverrides);
        }

        if (! AdminEmailTemplate::isAuthSlug($slug)) {
            return [
                'html' => '<p>Preview is not available for this template.</p>',
                'subject' => AdminEmailTemplate::defaultSubjectTemplateForSlug($slug),
            ];
        }

        return $this->buildAuthEmail($slug, $fieldOverrides, $placeholderOverrides);
    }

    /**
     * @return array<string, string>
     */
    public function authPreviewPlaceholders(): array
    {
        return [
            'name' => 'User',
            'otp_code' => '123456',
            'expires_in_minutes' => (string) UserLoginOtp::expirationMinutes(),
            'verify_link' => '',
            'year' => now()->format('Y'),
        ];
    }

    /**
     * @return array{html: string, subject: string}
     */
    public function buildAuthEmail(string $slug, array $fieldOverrides = [], array $placeholderOverrides = []): array
    {
        $basePlaceholders = $this->authPreviewPlaceholders();
        if ($slug === AdminEmailTemplate::SLUG_RESET_PASSWORD && ! array_key_exists('expires_in_minutes', $placeholderOverrides)) {
            $basePlaceholders['expires_in_minutes'] = (string) AdminEmailTemplate::passwordResetLinkExpirationMinutes();
        }
        $placeholders = array_merge($basePlaceholders, $placeholderOverrides);
        $row = $this->activeRow($slug);

        $subjectTemplate = null;
        if (array_key_exists('subject_template', $fieldOverrides)) {
            $subjectTemplate = $fieldOverrides['subject_template'];
        } elseif ($row !== null && filled($row->subject_template)) {
            $subjectTemplate = $row->subject_template;
        } else {
            $subjectTemplate = AdminEmailTemplate::defaultSubjectTemplateForSlug($slug);
        }

        $intro = $this->interpolate(
            $fieldOverrides['intro_html'] ?? $row?->intro_html,
            $placeholders
        );
        $bodyTemplate = $fieldOverrides['body_html'] ?? $row?->body_html;
        $bodyTemplate = $bodyTemplate ?? '';

        $bodyHtml = null;
        $otpBodyBeforeHtml = null;
        $otpBodyAfterHtml = null;
        $ctaFooterHtml = null;

        if ($slug === AdminEmailTemplate::SLUG_LOGIN_OTP && str_contains($bodyTemplate, '{{otp_code}}')) {
            $segments = explode('{{otp_code}}', $bodyTemplate, 2);
            $beforeRaw = $segments[0] ?? '';
            $afterRaw = $segments[1] ?? '';
            $otpBodyBeforeHtml = $this->interpolate($beforeRaw, $placeholders);
            $otpBodyAfterHtml = $this->interpolate($afterRaw, $placeholders);
        } elseif ($slug === AdminEmailTemplate::SLUG_RESET_PASSWORD && str_contains($bodyTemplate, '{{cta_button}}')) {
            $segments = explode('{{cta_button}}', $bodyTemplate, 2);
            $beforeRaw = $segments[0] ?? '';
            $afterRaw = $segments[1] ?? '';
            $otpBodyBeforeHtml = $this->interpolate($beforeRaw, $placeholders);
            $ctaFooterHtml = $this->interpolate($afterRaw, $placeholders);
        } else {
            $bodyHtml = $this->interpolate($bodyTemplate, $placeholders);
        }

        $footer = $this->interpolate(
            $fieldOverrides['footer_html'] ?? $row?->footer_html ?? AdminEmailTemplate::DEFAULT_COPYRIGHT_LINE,
            $placeholders
        );

        $subject = $this->normalizeMailSubject($this->interpolate($subjectTemplate, $placeholders));

        $verifyLink = trim((string) ($placeholders['verify_link'] ?? ''));
        $isPasswordSlug = in_array($slug, [
            AdminEmailTemplate::SLUG_RESET_PASSWORD,
            AdminEmailTemplate::SLUG_SETUP_PASSWORD,
        ], true);

        $ctaUrl = null;
        $ctaAlign = 'left';
        if ($isPasswordSlug) {
            $ctaUrl = $verifyLink !== '' ? $verifyLink : '#';
            $ctaAlign = 'center';
        }

        $otpCode = $slug === AdminEmailTemplate::SLUG_LOGIN_OTP
            ? ($placeholders['otp_code'] ?? null)
            : null;

        $ctaLabel = AdminEmailTemplate::defaultCtaLabelForSlug($slug) ?? 'Continue';

        $html = view('emails.admin._layout', [
            'headline' => '',
            'greetingName' => '',
            'htmlTitle' => $subject,
            'introHtml' => $intro,
            'bodyHtml' => filled($bodyHtml) ? $bodyHtml : null,
            'otpBodyBeforeHtml' => filled($otpBodyBeforeHtml ?? '') ? $otpBodyBeforeHtml : null,
            'otpBodyAfterHtml' => filled($otpBodyAfterHtml ?? '') ? $otpBodyAfterHtml : null,
            'ctaFooterHtml' => filled($ctaFooterHtml ?? '') ? $ctaFooterHtml : null,
            'rows' => [],
            'ticket' => null,
            'ctaUrl' => $ctaUrl,
            'ctaLabel' => $ctaLabel,
            'ctaAlign' => $ctaAlign,
            'companyId' => null,
            'copyrightLine' => $footer,
            'otpCode' => filled($otpCode) ? (string) $otpCode : null,
        ])->render();

        return ['html' => $html, 'subject' => $subject];
    }

    /**
     * @param  array<string, string|int>  $placeholderOverrides
     * @return array{html: string, subject: string}
     */
    public function buildOpenAttendanceReminderEmail(
        array $fieldOverrides = [],
        array $placeholderOverrides = []
    ): array {
        $slug = AdminEmailTemplate::SLUG_OPEN_ATTENDANCE_REMINDER;
        $placeholders = array_merge([
            'year' => now()->format('Y'),
            'check_in_count' => '12',
            'check_out_count' => '10',
            'not_checked_out_count' => '2',
            'report_date' => '10/09/2026',
            'attendance_summary_cards' => app(OpenAttendanceSummaryCardsHtml::class)->render(12, 10, 2),
            'attendance_table' => app(OpenAttendanceTableHtml::class)->renderPreviewSample(),
        ], $placeholderOverrides);

        $row = $this->activeRow($slug);

        $subjectTemplate = $fieldOverrides['subject_template']
            ?? $row?->subject_template
            ?? AdminEmailTemplate::defaultSubjectTemplateForSlug($slug);

        $intro = $this->interpolate(
            $fieldOverrides['intro_html'] ?? $row?->intro_html,
            $placeholders
        );
        $bodyTemplate = $fieldOverrides['body_html'] ?? $row?->body_html;
        $bodyWithTable = $this->interpolate($bodyTemplate, $placeholders);
        $footer = $this->interpolate(
            $fieldOverrides['footer_html'] ?? $row?->footer_html ?? AdminEmailTemplate::DEFAULT_COPYRIGHT_LINE,
            $placeholders
        );

        $subject = $this->normalizeMailSubject($this->interpolate($subjectTemplate, $placeholders));

        $html = view('emails.admin._layout', [
            'headline' => '',
            'greetingName' => '',
            'htmlTitle' => $subject,
            'introHtml' => $intro,
            'bodyHtml' => filled($bodyWithTable) ? $bodyWithTable : null,
            'otpBodyBeforeHtml' => null,
            'otpBodyAfterHtml' => null,
            'ctaFooterHtml' => null,
            'rows' => [],
            'ticket' => null,
            'ctaUrl' => null,
            'ctaLabel' => null,
            'ctaAlign' => 'center',
            'companyId' => $row?->company_id,
            'copyrightLine' => $footer,
            'otpCode' => null,
        ])->render();

        return ['html' => $html, 'subject' => $subject];
    }

    protected function activeRow(string $slug): ?AdminEmailTemplate
    {
        $query = AdminEmailTemplate::query()
            ->where('slug', $slug)
            ->where('status', true);

        $user = auth()->user();
        if ($user && isset($user->company_id) && $user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        return $query->first();
    }

    public function renderPreviewHtml(string $slug, array $fieldOverrides = [], array $placeholderOverrides = []): string
    {
        return $this->buildPreview($slug, $fieldOverrides, $placeholderOverrides)['html'];
    }
}
