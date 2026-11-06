<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\Attendance;
use App\Modules\Core\Models\Company;
use Illuminate\Support\Collection;

/**
 * HTML table for open-attendance reminder emails (inline styles for mail clients).
 */
class OpenAttendanceTableHtml
{
    /**
     * @param  Collection<int, Attendance>|array<int, Attendance>  $attendances
     */
    public function render(Collection|array $attendances, Company $company): string
    {
        $rows = $attendances instanceof Collection ? $attendances : collect($attendances);

        if ($rows->isEmpty()) {
            return '<p style="margin:0;font-size:15px;line-height:1.6;color:#71717a;">No students have not checked out today.</p>';
        }

        $body = '';
        foreach ($rows as $attendance) {
            $student = $attendance->student;
            $studentCode = e($student?->student_id ?? '-');
            $studentName = e($student?->name ?? '-');
            $checkIn = e($this->formatCheckIn($attendance->check_in_time));

            $body .= '<tr style="background-color:#ffffff;border-bottom:1px solid #e4e4e7;">'
                .'<td align="left" style="padding:12px 14px;font-size:15px;color:#18181b;">'.$studentCode.'</td>'
                .'<td align="left" style="padding:12px 14px;font-size:15px;color:#18181b;">'.$studentName.'</td>'
                .'<td align="left" style="padding:12px 14px;font-size:15px;color:#18181b;">'.$checkIn.'</td>'
                .'</tr>';
        }

        return '<table border="0" cellpadding="0" cellspacing="0" width="100%"'
            .' style="border-collapse:collapse;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,\'Helvetica Neue\',Arial,sans-serif;font-size:15px;color:#18181b;border:1px solid #e4e4e7;border-radius:8px;overflow:hidden;">'
            .'<tr style="background-color:#f4f4f5;">'
            .'<th align="left" style="padding:12px 14px;font-size:14px;font-weight:700;color:#09090b;border-bottom:1px solid #e4e4e7;">Student ID</th>'
            .'<th align="left" style="padding:12px 14px;font-size:14px;font-weight:700;color:#09090b;border-bottom:1px solid #e4e4e7;">Name</th>'
            .'<th align="left" style="padding:12px 14px;font-size:14px;font-weight:700;color:#09090b;border-bottom:1px solid #e4e4e7;">Check In</th>'
            .'</tr>'
            .$body
            .'</table>';
    }

    /**
     * @return list<array{student_id: string, name: string, check_in: string}>
     */
    public function previewSampleRows(): array
    {
        return [
            [
                'student_id' => '12345678',
                'name' => 'Sample Student',
                'check_in' => '08:00 AM',
            ],
            [
                'student_id' => '87654321',
                'name' => 'Another Student',
                'check_in' => '09:30 AM',
            ],
        ];
    }

    public function renderPreviewSample(): string
    {
        $body = '';
        foreach ($this->previewSampleRows() as $row) {
            $body .= '<tr style="background-color:#ffffff;border-bottom:1px solid #e4e4e7;">'
                .'<td align="left" style="padding:12px 14px;font-size:15px;color:#18181b;">'.e($row['student_id']).'</td>'
                .'<td align="left" style="padding:12px 14px;font-size:15px;color:#18181b;">'.e($row['name']).'</td>'
                .'<td align="left" style="padding:12px 14px;font-size:15px;color:#18181b;">'.e($row['check_in']).'</td>'
                .'</tr>';
        }

        return '<table border="0" cellpadding="0" cellspacing="0" width="100%"'
            .' style="border-collapse:collapse;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,\'Helvetica Neue\',Arial,sans-serif;font-size:15px;color:#18181b;border:1px solid #e4e4e7;border-radius:8px;overflow:hidden;">'
            .'<tr style="background-color:#f4f4f5;">'
            .'<th align="left" style="padding:12px 14px;font-size:14px;font-weight:700;color:#09090b;border-bottom:1px solid #e4e4e7;">Student ID</th>'
            .'<th align="left" style="padding:12px 14px;font-size:14px;font-weight:700;color:#09090b;border-bottom:1px solid #e4e4e7;">Name</th>'
            .'<th align="left" style="padding:12px 14px;font-size:14px;font-weight:700;color:#09090b;border-bottom:1px solid #e4e4e7;">Check In</th>'
            .'</tr>'
            .$body
            .'</table>';
    }

    private function formatCheckIn(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $string = is_string($value) ? trim($value) : (string) $value;
        if (strlen($string) >= 5 && preg_match('/^\d{1,2}:\d{2}/', $string) === 1) {
            try {
                return \Carbon\Carbon::createFromFormat('H:i:s', strlen($string) >= 8 ? substr($string, 0, 8) : $string.':00')
                    ->format('h:i A');
            } catch (\Throwable) {
                try {
                    return \Carbon\Carbon::createFromFormat('H:i', substr($string, 0, 5))->format('h:i A');
                } catch (\Throwable) {
                    return substr($string, 0, 5);
                }
            }
        }

        return $string;
    }
}
