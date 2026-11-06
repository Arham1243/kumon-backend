<?php

namespace App\Modules\Core\Http\Controllers;

use App\Modules\Administration\Http\Controllers\AdminOrionController;
use App\Modules\Core\Http\Requests\AttendanceRequest;
use App\Modules\Core\Http\Resources\AttendanceResource;
use App\Modules\Core\Models\Attendance;
use App\Modules\Core\Models\Student;
use App\Modules\Core\Services\AttendanceListQuery;
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

        $customFilters = $request->input('customFilters');
        if (! is_array($customFilters)) {
            return $query;
        }

        AttendanceListQuery::applySearchCustomFilters($query, $customFilters, true);

        return $query;
    }

    public function summary(Request $request)
    {
        $customFilters = $request->input('customFilters', []);
        if (! is_array($customFilters)) {
            $customFilters = [];
        }

        $data = AttendanceListQuery::summaryCounts($customFilters);

        return response()->json(['data' => $data]);
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
