<?php

namespace App\Modules\Core\Models;

use App\Traits\BelongsToCompany;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Student extends Model
{
    use BelongsToCompany;
    use HasUuid;

    protected $table = 'students';

    protected $guarded = ['id', 'uuid', 'created_at', 'updated_at'];

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function dependencies(): array
    {
        return [
            'attendances' => $this->attendances()->exists(),
        ];
    }

    protected static function booted()
    {
        static::deleting(function (Student $student) {
            $dependencies = $student->dependencies();

            if (in_array(true, $dependencies, true)) {
                throw ValidationException::withMessages([
                    'student' => 'This student has attendance records and cannot be deleted.',
                ]);
            }
        });
    }
}
