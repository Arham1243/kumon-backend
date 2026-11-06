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

    protected $guarded = ['id', 'uuid', 'created_at', 'updated_at', 'card_assigned_at'];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => 'boolean',
            'maths' => 'boolean',
            'reading' => 'boolean',
            'tuition_fee' => 'decimal:2',
            'card_assigned_at' => 'datetime',
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

    public function dependencies(): array
    {
        return [
            'attendances' => $this->attendances()->exists(),
        ];
    }

    /**
     * Next business student number for the current center: highest student_id plus one.
     * Pass $lock while already inside a transaction so concurrent creates cannot take the same number.
     */
    public static function nextStudentId(bool $lock = false): int
    {
        $query = static::query();

        if ($lock) {
            $max = $query
                ->orderByDesc('student_id')
                ->lockForUpdate()
                ->value('student_id');
        } else {
            $max = $query->max('student_id');
        }

        return ((int) $max) + 1;
    }

    protected static function booted()
    {
        static::saving(function (Student $student) {
            if (! $student->isDirty('card_uid')) {
                return;
            }

            $oldNorm = CardUid::normalize($student->getOriginal('card_uid'));
            $newNorm = CardUid::normalize($student->card_uid);

            if ($newNorm === null) {
                $student->card_assigned_at = null;

                return;
            }

            if ($oldNorm !== $newNorm) {
                $student->card_assigned_at = now();
            }
        });

        static::updating(function (Student $student) {
            if ($student->isDirty('student_id')) {
                $student->student_id = $student->getOriginal('student_id');
            }
        });

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
