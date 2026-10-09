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
            return '';
        }

        $body = '';
        foreach ($rows as $attendance) {
            $student = $attendance->student;
            $studentCode = e($student?->student_id ?? '-');
            $studentName = e($student?->name ?? '-');
            $date = e(format_date($attendance->date, null, $company));
            $checkIn = e($this->formatCheckIn($attendance->check_in_time));

            $body .= '<tr style="border-bottom:1px solid #e0e0e0;">'
                .'<td align="left" style="padding:10px;">'.$studentCode.'</td>'
                .'<td align="left" style="padding:10px;">'.$studentName.'</td>'
                .'<td align="left" style="padding:10px;">'.$date.'</td>'
                .'<td align="left" style="padding:10px;">'.$checkIn.'</td>'
                .'</tr>';
        }

        return '<table border="0" cellpadding="10" cellspacing="0" width="100%"'
            .' style="border-collapse:collapse;font-family:sans-serif;font-size:15px;color:#000000;background-color:#f9f9f9;margin-top:16px;">'
            .'<tr style="background-color:#e0e0e0;color:#000000;">'
            .'<th align="left" style="padding:12px;font-weight:bold;">Student ID</th>'
            .'<th align="left" style="padding:12px;font-weight:bold;">Name</th>'
            .'<th align="left" style="padding:12px;font-weight:bold;">Date</th>'
            .'<th align="left" style="padding:12px;font-weight:bold;">Check In</th>'
            .'</tr>'
            .$body
            .'</table>';
    }

    /**
     * @return list<array{student_id: string, name: string, date: string, check_in: string}>
     */
    public function previewSampleRows(): array
    {
        return [
            [
                'student_id' => '12345678',
                'name' => 'Sample Student',
                'date' => '10/09/2026',
                'check_in' => '08:00 AM',
            ],
            [
                'student_id' => '87654321',
                'name' => 'Another Student',
                'date' => '10/09/2026',
                'check_in' => '09:30 AM',
            ],
        ];
    }

    public function renderPreviewSample(): string
    {
        $body = '';
        foreach ($this->previewSampleRows() as $row) {
            $body .= '<tr style="border-bottom:1px solid #e0e0e0;">'
                .'<td align="left" style="padding:10px;">'.e($row['student_id']).'</td>'
                .'<td align="left" style="padding:10px;">'.e($row['name']).'</td>'
                .'<td align="left" style="padding:10px;">'.e($row['date']).'</td>'
                .'<td align="left" style="padding:10px;">'.e($row['check_in']).'</td>'
                .'</tr>';
        }

        return '<table border="0" cellpadding="10" cellspacing="0" width="100%"'
            .' style="border-collapse:collapse;font-family:sans-serif;font-size:15px;color:#000000;background-color:#f9f9f9;margin-top:16px;">'
            .'<tr style="background-color:#e0e0e0;color:#000000;">'
            .'<th align="left" style="padding:12px;font-weight:bold;">Student ID</th>'
            .'<th align="left" style="padding:12px;font-weight:bold;">Name</th>'
            .'<th align="left" style="padding:12px;font-weight:bold;">Date</th>'
            .'<th align="left" style="padding:12px;font-weight:bold;">Check In</th>'
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
