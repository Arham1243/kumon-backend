<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsTabularExcel;
use App\Http\Controllers\Concerns\ExportsTabularPdf;
use App\Modules\Core\Models\Attendance;
use App\Modules\Core\Services\AttendanceListQuery;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceExportController extends Controller
{
    use ExportsTabularExcel;
    use ExportsTabularPdf;

    private const HEADER_MAP = [
        'student.name' => 'Name',
        'student.student_id' => 'Student ID',
        'date' => 'Date',
        'check_in_time' => 'Check In',
        'check_out_time' => 'Check Out',
    ];

    public function export(Request $request): StreamedResponse|Response|\Illuminate\Http\JsonResponse
    {
        $format = strtolower((string) $request->input('format', 'excel'));
        if (! in_array($format, ['excel', 'pdf'], true)) {
            return response()->json(['error' => 'Invalid format'], 400);
        }

        $columns = array_values(array_filter(
            $request->input('columns', []),
            fn ($col) => is_string($col) && $col !== ''
        ));
        if ($columns === []) {
            $columns = array_keys(self::HEADER_MAP);
        }

        $filters = $request->input('filters', []);
        $customFilters = $request->input('customFilters', []);
        $sort = $request->input('sort', []);

        $items = AttendanceListQuery::build($filters, $customFilters, $sort)->get();

        $data = $items->map(function (Attendance $attendance) use ($columns) {
            return $this->rowForExport($attendance, $columns);
        })->all();

        if ($format === 'pdf') {
            return $this->streamPdfDownload(
                'Attendance',
                $data,
                $columns,
                self::HEADER_MAP,
                'attendance.pdf'
            );
        }

        $spreadsheet = $this->spreadsheetFromTabularRows(
            $data,
            $columns,
            self::HEADER_MAP,
            ['student.student_id']
        );

        return $this->streamXlsxDownload($spreadsheet, 'attendance.xlsx');
    }

    /**
     * @param  array<int, string>  $columns
     * @return array<string, mixed>
     */
    private function rowForExport(Attendance $attendance, array $columns): array
    {
        $row = [];
        foreach ($columns as $col) {
            $row[$col] = $this->exportValue($attendance, $col);
        }

        return $row;
    }

    private function exportValue(Attendance $attendance, string $column): mixed
    {
        return match ($column) {
            'student.name' => $attendance->student?->name,
            'student.student_id' => $attendance->student?->student_id,
            'date' => $attendance->date ? format_date($attendance->date) : null,
            'check_in_time' => $this->formatTimeForExport($attendance->check_in_time),
            'check_out_time' => $this->formatTimeForExport($attendance->check_out_time),
            default => Str::contains($column, '.')
                ? $this->nestedValue($attendance, $column)
                : ($attendance->{$column} ?? null),
        };
    }

    private function nestedValue(Attendance $attendance, string $column): mixed
    {
        [$relation, $field] = explode('.', $column, 2);
        $related = $attendance->{$relation};

        return $related ? ($related->{$field} ?? null) : null;
    }

    private function formatTimeForExport(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = trim((string) $value);
        try {
            if (strlen($raw) >= 8) {
                return Carbon::createFromFormat('H:i:s', substr($raw, 0, 8))->format('h:i A');
            }

            return Carbon::createFromFormat('H:i', substr($raw, 0, 5))->format('h:i A');
        } catch (\Throwable) {
            return $raw;
        }
    }
}
