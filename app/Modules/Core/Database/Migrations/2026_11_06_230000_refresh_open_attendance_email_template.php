<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $name = 'Daily Attendance Summary Distribution Report';
        $subjectTemplate = 'Daily Summary Report - {{report_date}}';
        $introHtml = '';
        $bodyHtml = '<p style="margin:0 0 8px 0;">Students Check In on today ({{check_in_count}})</p>'
            .'<p style="margin:0 0 16px 0;">Students Check Out on today ({{check_out_count}})</p>'
            .'<p style="margin:0 0 8px 0;">Students Who have not Checked Out today (list below)</p>'
            .'<div style="margin:0 0 16px 0;">{{attendance_table}}</div>'
            .'<p style="margin:0;">If checkout is not recorded, the system will close these entries automatically at the end of the day.</p>';

        DB::table('admin_email_templates')
            ->where('slug', 'open-attendance-reminder')
            ->update([
                'name' => $name,
                'subject_template' => $subjectTemplate,
                'intro_html' => $introHtml,
                'body_html' => $bodyHtml,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $name = 'Open Attendance Reminder to Admin';
        $subjectTemplate = 'Open Attendance Reminder';
        $introHtml = '<p style="margin:0;">Hi {{name}},</p>';
        $bodyHtml = '<p style="margin:0 0 12px 0;">The following students checked in today but do not have a checkout recorded yet. Please mark their checkout on the attendance screen so tomorrow runs smoothly.</p>'
            .'<p style="margin:0 0 12px 0;">{{attendance_table}}</p>'
            .'<p style="margin:0;">If checkout is not recorded, the system will close these entries automatically at the end of the day.</p>';

        DB::table('admin_email_templates')
            ->where('slug', 'open-attendance-reminder')
            ->update([
                'name' => $name,
                'subject_template' => $subjectTemplate,
                'intro_html' => $introHtml,
                'body_html' => $bodyHtml,
                'updated_at' => now(),
            ]);
    }
};
