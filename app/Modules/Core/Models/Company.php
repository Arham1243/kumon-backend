<?php

namespace App\Modules\Core\Models;

use App\Enums\ActivityLogName;
use App\Enums\CompanyTimezone;
use App\Traits\AuditsChanges;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use AuditsChanges;
    use HasUuid;

    protected $table = 'admin_companies';

    protected $guarded = ['id', 'uuid', 'created_at', 'updated_at'];

    protected $attributes = [
        'timezone' => CompanyTimezone::AmericaNewYork->value,
    ];

    protected function casts(): array
    {
        return [];
    }

    public function getActivityLogName(): ActivityLogName
    {
        return ActivityLogName::Company;
    }

    protected function activityLogExcept(): array
    {
        return ['logo', 'updated_at', 'created_at'];
    }
}
