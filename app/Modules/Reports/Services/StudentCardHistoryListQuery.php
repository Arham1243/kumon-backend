<?php

namespace App\Modules\Reports\Services;

use App\Modules\Core\Models\StudentCardHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class StudentCardHistoryListQuery
{
    /**
     * @param  array<int, array<string, mixed>>  $filters
     * @param  array<int, array<string, mixed>>  $customFilters
     * @param  array<int, array<string, mixed>>  $sort
     */
    public static function build(array $filters = [], array $customFilters = [], array $sort = []): Builder
    {
        $query = StudentCardHistory::query()->with([
            'student:id,student_id,name,company_id',
        ]);

        foreach ($filters as $filter) {
            self::applyFilter($query, $filter);
        }

        foreach ($customFilters as $customFilter) {
            $field = $customFilter['field'] ?? null;
            $value = $customFilter['value'] ?? null;
            if ($field === 'assigned_at_range' && is_array($value) && count($value) === 2) {
                [$from, $to] = $value;
                if ($from) {
                    $query->whereDate('student_card_histories.assigned_at', '>=', $from);
                }
                if ($to) {
                    $query->whereDate('student_card_histories.assigned_at', '<=', $to);
                }
            }
        }

        if ($sort === []) {
            $query->orderByDesc('student_card_histories.assigned_at');
        } else {
            foreach ($sort as $sortItem) {
                $field = $sortItem['field'] ?? null;
                $direction = strtolower((string) ($sortItem['direction'] ?? 'asc'));
                if (! $field || ! in_array($direction, ['asc', 'desc'], true)) {
                    continue;
                }

                if ($field === 'student.student_id' || $field === 'student.name') {
                    self::ensureStudentJoin($query);
                    $column = $field === 'student.student_id'
                        ? 'sch_report_students.student_id'
                        : 'sch_report_students.name';
                    $query->orderBy($column, $direction);
                    continue;
                }

                if (Str::contains($field, '.')) {
                    continue;
                }

                $query->orderBy('student_card_histories.'.$field, $direction);
            }
        }

        return $query->select('student_card_histories.*');
    }

    private static function ensureStudentJoin(Builder $query): void
    {
        $joins = $query->getQuery()->joins ?? [];
        foreach ($joins as $join) {
            if ($join->table === 'students as sch_report_students') {
                return;
            }
        }

        $query->leftJoin(
            'students as sch_report_students',
            'sch_report_students.id',
            '=',
            'student_card_histories.student_id'
        );
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    private static function applyFilter(Builder $query, array $filter): void
    {
        $field = $filter['field'] ?? null;
        if ($field === null || $field === '') {
            return;
        }

        $operator = $filter['operator'] ?? '=';
        $value = $filter['value'] ?? null;

        if ($value === null || $value === '') {
            return;
        }

        if (Str::contains($field, '.')) {
            [$relation, $column] = explode('.', $field, 2);
            $query->whereHas($relation, function (Builder $inner) use ($column, $operator, $value): void {
                if ($operator === 'like') {
                    $inner->where($column, 'like', $value);
                } else {
                    $inner->where($column, $operator, $value);
                }
            });

            return;
        }

        $column = 'student_card_histories.'.$field;
        if ($operator === 'like') {
            $query->where($column, 'like', $value);
        } elseif ($operator === 'in' && is_array($value)) {
            $query->whereIn($column, $value);
        } else {
            $query->where($column, $operator, $value);
        }
    }
}
