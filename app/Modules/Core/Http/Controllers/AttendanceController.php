<?php

namespace App\Modules\Core\Http\Controllers;

use App\Modules\Administration\Http\Controllers\AdminOrionController;
use App\Modules\Core\Http\Requests\AttendanceRequest;
use App\Modules\Core\Http\Resources\AttendanceResource;
use App\Modules\Core\Models\Attendance;
use App\Modules\Core\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Requests\Request as OrionRequest;

class AttendanceController extends AdminOrionController
{
    use DisableAuthorization;

    protected $model = Attendance::class;

    protected $request = AttendanceRequest::class;

    protected $resource = AttendanceResource::class;

    public function searchableBy(): array
    {
        return [];
    }

    public function sortableBy(): array
    {
        return ['date', 'check_in_time', 'check_out_time', 'created_at'];
    }

    public function filterableBy(): array
    {
        return [
            'student_id',
            'date',
            'check_in_time',
            'check_out_time',
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

    protected function buildFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildFetchQuery($request, $requestedRelations);

        $this->applyCustomFilters($query, $request);

        return $query;
    }

    protected function applyCustomFilters(Builder $query, Request $request): Builder
    {
        if (! $request->filled('customFilters')) {
            return $query;
        }

        $table = (new Attendance)->getTable();

        $handlers = [
            'date_range' => function (Builder $query, mixed $value) use ($table): void {
                if (! is_array($value) || count($value) !== 2) {
                    return;
                }

                [$from, $to] = $value;
                if ($from) {
                    $query->whereDate("{$table}.date", '>=', $from);
                }
                if ($to) {
                    $query->whereDate("{$table}.date", '<=', $to);
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

    protected function beforeSave(OrionRequest $request, Model $entity): void
    {
        if (! $entity instanceof Attendance) {
            return;
        }

        $user = $request->user();
        if ($user && $user->company_id && empty($entity->company_id)) {
            $entity->company_id = $user->company_id;
        }
    }

    public function deleteAttendance(Attendance $attendance)
    {
        $attendance->delete();

        return response()->json(['message' => 'Attendance deleted successfully.']);
    }

    public function studentOptions(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $query = Student::query()
            ->select(['id', 'uuid', 'student_id', 'name'])
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%");
            });
        }

        $students = $query->limit(300)->get();

        return response()->json(['data' => $students]);
    }
}
