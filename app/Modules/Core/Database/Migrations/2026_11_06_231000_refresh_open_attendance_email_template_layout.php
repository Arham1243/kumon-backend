<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $bodyHtml = '<h1 style="margin:0 0 8px 0;font-size:22px;font-weight:700;color:#18181b;line-height:1.3;">Daily Attendance Summary</h1>'
            .'<p style="margin:0 0 20px 0;font-size:14px;line-height:1.5;color:#71717a;">{{report_date}}</p>'
            .'<div style="margin:0 0 20px 0;height:1px;background-color:#e4e4e7;line-height:1px;font-size:1px;">&nbsp;</div>'
            .'{{attendance_summary_cards}}'
            .'<p style="margin:0 0 12px 0;font-size:15px;line-height:1.6;color:#18181b;font-weight:600;">Students who have not checked out today:</p>'
            .'<div style="margin:0 0 20px 0;">{{attendance_table}}</div>'
            .'<p style="margin:0;font-size:14px;line-height:1.6;color:#71717a;">If a check-out is not recorded, the system will close these entries automatically at the end of the day.</p>';

        DB::table('admin_email_templates')
            ->where('slug', 'open-attendance-reminder')
            ->update([
                'body_html' => $bodyHtml,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $bodyHtml = '<p style="margin:0 0 8px 0;">Students Check In on today ({{check_in_count}})</p>'
            .'<p style="margin:0 0 16px 0;">Students Check Out on today ({{check_out_count}})</p>'
            .'<p style="margin:0 0 8px 0;">Students Who have not Checked Out today (list below)</p>'
            .'<div style="margin:0 0 16px 0;">{{attendance_table}}</div>'
            .'<p style="margin:0;">If checkout is not recorded, the system will close these entries automatically at the end of the day.</p>';

        DB::table('admin_email_templates')
            ->where('slug', 'open-attendance-reminder')
            ->update([
                'body_html' => $bodyHtml,
                'updated_at' => now(),
            ]);
    }
};
