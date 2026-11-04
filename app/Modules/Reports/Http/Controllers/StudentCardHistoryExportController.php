<?php

namespace App\Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsTabularExcel;
use App\Http\Controllers\Concerns\ExportsTabularPdf;
use App\Modules\Core\Models\StudentCardHistory;
use App\Modules\Reports\Services\StudentCardHistoryListQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentCardHistoryExportController extends Controller
{
    use ExportsTabularExcel;
    use ExportsTabularPdf;

    private const HEADER_MAP = [
        'student.student_id' => 'Student ID',
        'student.name' => 'Name',
        'card_uid' => 'Card UID',
        'assigned_at' => 'Assigned at',
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

        $items = StudentCardHistoryListQuery::build($filters, $customFilters, $sort)->get();

        $data = $items->map(function (StudentCardHistory $row) use ($columns) {
            return $this->rowForExport($row, $columns);
        })->all();

        if ($format === 'pdf') {
            return $this->streamPdfDownload(
                'Student Cards',
                $data,
                $columns,
                self::HEADER_MAP,
                'student-cards.pdf'
            );
        }

        $spreadsheet = $this->spreadsheetFromTabularRows($data, $columns, self::HEADER_MAP);

        return $this->streamXlsxDownload($spreadsheet, 'student-cards.xlsx');
    }

    /**
     * @param  array<int, string>  $columns
     * @return array<string, mixed>
     */
    private function rowForExport(StudentCardHistory $row, array $columns): array
    {
        $out = [];
        foreach ($columns as $col) {
            $out[$col] = $this->exportValue($row, $col);
        }

        return $out;
    }

    private function exportValue(StudentCardHistory $row, string $column): mixed
    {
        return match ($column) {
            'assigned_at' => $row->assigned_at ? format_date($row->assigned_at) : null,
            'student.student_id' => $row->student?->student_id,
            'student.name' => $row->student?->name,
            default => Str::contains($column, '.')
                ? $this->nestedValue($row, $column)
                : ($row->{$column} ?? null),
        };
    }

    private function nestedValue(StudentCardHistory $row, string $column): mixed
    {
        [$relation, $field] = explode('.', $column, 2);
        $related = $row->{$relation};

        return $related ? ($related->{$field} ?? null) : null;
    }
}
