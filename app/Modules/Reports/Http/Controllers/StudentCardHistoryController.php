<?php

namespace App\Modules\Reports\Http\Controllers;

use App\Modules\Administration\Http\Controllers\AdminOrionController;
use App\Modules\Core\Models\StudentCardHistory;
use App\Modules\Reports\Http\Resources\StudentCardHistoryReportResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Orion\Concerns\DisableAuthorization;

class StudentCardHistoryController extends AdminOrionController
{
    use DisableAuthorization;

    protected $model = StudentCardHistory::class;

    protected $resource = StudentCardHistoryReportResource::class;

    public function searchableBy(): array
    {
        return [
            'card_uid',
            'student.name',
            'student.student_id',
        ];
    }

    public function includes(): array
    {
        return ['student'];
    }

    public function alwaysIncludes(): array
    {
        return ['student'];
    }

    public function sortableBy(): array
    {
        return [
            'assigned_at',
            'card_uid',
            'student.student_id',
            'student.name',
        ];
    }

    public function filterableBy(): array
    {
        return [
            'card_uid',
            'student.student_id',
            'student.name',
        ];
    }

    protected function buildFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildFetchQuery($request, $requestedRelations);

        $query->with([
            'student:id,student_id,name,company_id',
        ]);

        $this->applyCustomFilters($query, $request);

        return $query;
    }

    protected function applyCustomFilters(Builder $query, Request $request): Builder
    {
        if (! $request->filled('customFilters')) {
            return $query;
        }

        $handlers = [
            'assigned_at_range' => function (Builder $query, mixed $value): void {
                if (is_array($value) && count($value) === 2) {
                    [$from, $to] = $value;
                    if ($from) {
                        $query->whereDate('student_card_histories.assigned_at', '>=', $from);
                    }
                    if ($to) {
                        $query->whereDate('student_card_histories.assigned_at', '<=', $to);
                    }
                }
            },
        ];

        foreach ($request->input('customFilters') as $filter) {
            $field = $filter['field'] ?? null;
            $value = $filter['value'] ?? null;
            if ($field && isset($handlers[$field]) && $value !== null) {
                $handlers[$field]($query, $value);
            }
        }

        return $query;
    }
}
