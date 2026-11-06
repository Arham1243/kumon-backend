<?php

namespace App\Modules\Administration\Database\Seeders;

use App\Modules\Administration\Models\Admin\AdminEmailTemplate;
use App\Modules\Core\Models\Company;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->orderBy('id')->first();
        if (! $company) {
            return;
        }

        $mail = AdminEmailTemplate::defaultMailEnvelope();

        $templates = [
            [
                'slug' => AdminEmailTemplate::SLUG_LOGIN_OTP,
                'name' => 'OTP Email',
                'from' => 'verification',
                'is_system' => true,
                'intro_html' => '',
                'body_html' => '<p style="margin:0;">Here is a code you requested. This will expire in {{expires_in_minutes}} minutes. If you haven\'t attempted to log in, please contact your administrator.</p>'
                    .'<p style="margin:16px 0 0 0;color:red;font-weight:600;font-style:italic;">Never share this code with anyone.</p>'
                    .'<p style="margin:0;">{{otp_code}}</p>'
                    .'<p style="margin:16px 0 0 0;">If you haven\'t requested this OTP, for security reasons, we highly recommend changing your password.</p>',
                'footer_html' => AdminEmailTemplate::DEFAULT_COPYRIGHT_LINE,
            ],
            [
                'slug' => AdminEmailTemplate::SLUG_RESET_PASSWORD,
                'name' => 'Password Reset Email',
                'from' => 'info',
                'is_system' => true,
                'intro_html' => '',
                'body_html' => '<p style="margin:0;">Here is a link you requested. This will expire in {{expires_in_minutes}} minutes. If you haven\'t attempted to reset your password, please contact your administrator.</p>'
                    .'{{cta_button}}'
                    .'<p style="margin:16px 0 0 0;">If you haven\'t requested this password reset request, for security reasons, we highly recommend changing your password.</p>',
                'footer_html' => AdminEmailTemplate::DEFAULT_COPYRIGHT_LINE,
            ],
            [
                'slug' => AdminEmailTemplate::SLUG_SETUP_PASSWORD,
                'name' => 'Welcome Email',
                'from' => 'info',
                'is_system' => true,
                'intro_html' => '<p style="margin:0;">Hi {{name}},</p>',
                'body_html' => '<p style="margin:0;">Welcome aboard! To get started, simply activate your account by clicking the button below.</p>',
                'footer_html' => AdminEmailTemplate::DEFAULT_COPYRIGHT_LINE,
            ],
            [
                'slug' => AdminEmailTemplate::SLUG_OPEN_ATTENDANCE_REMINDER,
                'name' => 'Daily Attendance Summary Distribution Report',
                'from' => 'info',
                'is_system' => false,
                'subject_template' => 'Daily Summary Report - {{report_date}}',
                'intro_html' => '',
                'body_html' => '<h1 style="margin:0 0 8px 0;font-size:22px;font-weight:700;color:#18181b;line-height:1.3;">Daily Attendance Summary</h1>'
                    .'<p style="margin:0 0 20px 0;font-size:14px;line-height:1.5;color:#71717a;">{{report_date}}</p>'
                    .'<div style="margin:0 0 20px 0;height:1px;background-color:#e4e4e7;line-height:1px;font-size:1px;">&nbsp;</div>'
                    .'{{attendance_summary_cards}}'
                    .'<p style="margin:0 0 12px 0;font-size:15px;line-height:1.6;color:#18181b;font-weight:600;">Students who have not checked out today:</p>'
                    .'<div style="margin:0 0 20px 0;">{{attendance_table}}</div>'
                    .'<p style="margin:0;font-size:14px;line-height:1.6;color:#71717a;">If a check-out is not recorded, the system will close these entries automatically at the end of the day.</p>',
                'footer_html' => AdminEmailTemplate::DEFAULT_COPYRIGHT_LINE,
                'to_recipients' => [],
            ],
        ];

        foreach ($templates as $row) {
            $slug = $row['slug'];
            $attributes = [
                'slug' => $slug,
                'name' => $row['name'],
                'subject_template' => $row['subject_template']
                    ?? AdminEmailTemplate::defaultSubjectTemplateForSlug($slug),
                'from' => $row['from'] ?? $mail['from'],
                'display_name' => $mail['display_name'],
                'reply_to_email' => $mail['reply_to_email'],
                'reply_to_name' => $mail['reply_to_name'],
                'bcc_recipients' => $mail['bcc_recipients'],
                'to_recipients' => $row['to_recipients'] ?? null,
                'intro_html' => $row['intro_html'],
                'body_html' => $row['body_html'],
                'footer_html' => $row['footer_html'],
                'status' => $slug === AdminEmailTemplate::SLUG_OPEN_ATTENDANCE_REMINDER
                    ? false
                    : true,
                'is_system' => (bool) ($row['is_system'] ?? false),
            ];

            $template = AdminEmailTemplate::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('slug', $slug)
                ->first();
            if ($template === null) {
                $template = new AdminEmailTemplate;
                $template->company_id = $company->id;
            }
            $template->forceFill($attributes);
            $template->save();
        }

        AdminEmailTemplate::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereNotIn('slug', array_column($templates, 'slug'))
            ->delete();
    }
}
