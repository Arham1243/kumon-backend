<?php

namespace App\Modules\Core\Http\Controllers;

use App\Modules\Administration\Http\Controllers\AdminOrionController;
use App\Modules\Core\Http\Requests\ChangeStudentStatusRequest;
use App\Modules\Core\Http\Requests\StudentRequest;
use App\Modules\Core\Http\Resources\StudentResource;
use App\Modules\Core\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Requests\Request as OrionRequest;

class StudentController extends AdminOrionController
{
    use DisableAuthorization;

    protected $model = Student::class;

    protected $request = StudentRequest::class;

    protected $resource = StudentResource::class;

    public function searchableBy(): array
    {
        return [
            'student_id',
            'name',
            'parent_1_mobile',
            'parent_2_mobile',
            'card_uid',
        ];
    }

    public function sortableBy(): array
    {
        return [
            'student_id',
            'name',
            'parent_1_mobile',
            'parent_2_mobile',
            'start_date',
            'end_date',
            'tuition_fee',
            'card_uid',
            'status',
            'created_at',
        ];
    }

    public function filterableBy(): array
    {
        return [
            'student_id',
            'name',
            'parent_1_mobile',
            'parent_2_mobile',
            'tuition_fee',
            'card_uid',
            'status',
        ];
    }

    protected function beforeSave(OrionRequest $request, Model $entity): void
    {
        if (! $entity instanceof Student) {
            return;
        }

        $user = $request->user();
        if ($user && $user->company_id && empty($entity->company_id)) {
            $entity->company_id = $user->company_id;
        }
    }

    public function nextStudentId(): JsonResponse
    {
        return response()->json([
            'data' => [
                'student_id' => Student::nextStudentId(),
            ],
        ]);
    }

    protected function performStore(OrionRequest $request, Model $entity, array $attributes): void
    {
        if (! $entity instanceof Student) {
            parent::performStore($request, $entity, $attributes);

            return;
        }

        $attempts = 0;

        while (true) {
            try {
                DB::transaction(function () use ($request, $entity, $attributes) {
                    $this->performFill($request, $entity, $attributes);
                    $entity->student_id = Student::nextStudentId(true);
                    $entity->save();
                });

                return;
            } catch (QueryException $exception) {
                $attempts++;
                $entity->exists = false;

                if ($attempts >= 3 || ! $this->isDuplicateStudentId($exception)) {
                    throw $exception;
                }
            }
        }
    }

    private function isDuplicateStudentId(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'student_id')
            && ($exception->getCode() === '23000'
                || str_contains($message, '1062')
                || str_contains($message, 'Duplicate')
                || str_contains($message, 'UNIQUE constraint failed'));
    }

    public function changeStatus(ChangeStudentStatusRequest $request, Student $student)
    {
        if ($student->status) {
            $student->update([
                'status' => false,
                'end_date' => $request->validated('end_date'),
                'card_uid' => null,
            ]);
        } else {
            $student->update([
                'status' => true,
                'end_date' => null,
            ]);
        }

        return new StudentResource($student->fresh());
    }

    public function deleteStudent(Student $student)
    {
        $student->delete();

        return response()->json(['message' => 'Student deleted successfully.']);
    }
}
