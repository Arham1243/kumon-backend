<?php

namespace App\Modules\Core\Http\Controllers;

use App\Modules\Core\Http\Resources\StudentCardHistoryResource;
use App\Modules\Core\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\RelationController;
use Orion\Http\Requests\Request;

class StudentCardHistoriesController extends RelationController
{
    use DisableAuthorization;

    protected $model = Student::class;

    protected $relation = 'cardHistories';

    protected $resource = StudentCardHistoryResource::class;

    protected function parentKeyName(): string
    {
        return (new Student)->getRouteKeyName();
    }

    public function sortableBy(): array
    {
        return [
            'assigned_at',
            'card_uid',
        ];
    }

    public function filterableBy(): array
    {
        return [
            'card_uid',
        ];
    }

    protected function buildRelationFetchQuery(
        Request $request,
        Model $parentEntity,
        array $requestedRelations
    ): Relation {
        return parent::buildRelationFetchQuery($request, $parentEntity, $requestedRelations);
    }
}
