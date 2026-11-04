<?php

namespace App\Modules\Core\Models;

use App\Enums\ActivityLogName;
use App\Modules\Core\Support\CardUid;
use App\Traits\AuditsChanges;
use App\Traits\BelongsToCompany;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Contracts\Activity;

class Student extends Model
{
    use AuditsChanges;
    use BelongsToCompany;
    use HasUuid;

    protected $table = 'students';

    protected $guarded = ['id', 'uuid', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
        ];
    }

    public function getActivityLogName(): ActivityLogName
    {
        return ActivityLogName::Student;
    }

    protected function activityLogOnly(): ?array
    {
        return ['card_uid'];
    }

    public function shouldLogEvent(string $eventName): bool
    {
        if ($eventName === 'created') {
            return CardUid::normalize($this->card_uid) !== null;
        }

        if ($eventName === 'updated') {
            return $this->wasChanged('card_uid');
        }

        return false;
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $label = $this->activitySubjectLabel();
        if ($label !== null && $label !== '') {
            $properties = $activity->properties;
            $props = $properties instanceof \Illuminate\Support\Collection
                ? $properties->toArray()
                : (array) ($properties ?? []);
            $props['subject_label'] = $label;
            $activity->properties = $props;
        }

        $old = CardUid::normalize($this->getOriginal('card_uid'));
        $new = CardUid::normalize($this->card_uid);

        if ($eventName === 'created' && $new !== null) {
            $activity->description = 'Assigned card';

            return;
        }

        if ($eventName === 'updated') {
            if ($old === null && $new !== null) {
                $activity->description = 'Assigned card';
            } elseif ($old !== null && $new !== null) {
                $activity->description = 'Replaced card';
            } elseif ($old !== null && $new === null) {
                $activity->description = 'Removed card';
            }
        }
    }

    protected function activitySubjectLabel(): ?string
    {
        $name = $this->name ?? null;
        $sid = $this->student_id ?? null;
        if (is_string($name) && $name !== '' && $sid !== null) {
            return "{$name} ({$sid})";
        }

        return $this->defaultActivitySubjectLabel();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function cardHistories(): HasMany
    {
        return $this->hasMany(StudentCardHistory::class);
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
