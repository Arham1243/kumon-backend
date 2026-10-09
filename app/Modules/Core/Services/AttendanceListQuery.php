<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\Attendance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AttendanceListQuery
{
    /**
     * @param  array<int, array<string, mixed>>  $filters
     * @param  array<int, array<string, mixed>>  $customFilters
     * @param  array<int, array<string, mixed>>  $sort
     */
    public static function build(array $filters = [], array $customFilters = [], array $sort = []): Builder
    {
        $table = (new Attendance)->getTable();

        $query = Attendance::query()->with([
            'student:id,uuid,student_id,name',
        ]);

        foreach ($filters as $filter) {
            self::applyFilter($query, $filter, $table);
        }

        foreach ($customFilters as $customFilter) {
            $field = $customFilter['field'] ?? null;
            $value = $customFilter['value'] ?? null;
            if ($field === 'date_range' && is_array($value) && count($value) === 2) {
                [$from, $to] = $value;
                if ($from) {
                    $query->whereDate("{$table}.date", '>=', $from);
                }
                if ($to) {
                    $query->whereDate("{$table}.date", '<=', $to);
                }
            }
        }

        if ($sort === []) {
            $query->orderByDesc("{$table}.date");
        } else {
            foreach ($sort as $sortItem) {
                $field = $sortItem['field'] ?? null;
                $direction = strtolower((string) ($sortItem['direction'] ?? 'asc'));
                if (! $field || ! in_array($direction, ['asc', 'desc'], true)) {
                    continue;
                }
                if (Str::contains($field, '.')) {
                    [$relation, $column] = explode('.', $field, 2);
                    $query->whereHas($relation, function (Builder $q) use ($column, $direction): void {
                        $q->orderBy($column, $direction);
                    });
                } else {
                    $query->orderBy("{$table}.{$field}", $direction);
                }
            }
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    private static function applyFilter(Builder $query, array $filter, string $table): void
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

        $column = "{$table}.{$field}";
        if ($operator === 'like') {
            $query->where($column, 'like', $value);
        } elseif ($operator === 'in' && is_array($value)) {
            $query->whereIn($column, $value);
        } else {
            $query->where($column, $operator, $value);
        }
    }
}
