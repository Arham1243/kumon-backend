<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\Student;
use App\Modules\Core\Models\StudentCardHistory;
use App\Modules\Core\Support\CardUid;

class StudentCardService
{
    public function syncAfterStudentSave(Student $student): void
    {
        $uid = trim((string) ($student->card_uid ?? ''));
        if ($uid === '' || ! CardUid::isValid($uid)) {
            return;
        }

        $exists = StudentCardHistory::query()
            ->where('student_id', $student->id)
            ->whereRaw('UPPER(card_uid) = ?', [strtoupper($uid)])
            ->exists();

        if ($exists) {
            return;
        }

        StudentCardHistory::query()->create([
            'company_id' => $student->company_id,
            'student_id' => $student->id,
            'card_uid' => $uid,
            'assigned_at' => now(),
        ]);
    }
}
