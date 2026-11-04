<?php

namespace App\Modules\Reports\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentCardHistoryReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'card_uid' => $this->card_uid,
            'assigned_at' => $this->assigned_at,
            'student' => $this->whenLoaded('student', function () {
                if ($this->student === null) {
                    return null;
                }

                return [
                    'student_id' => $this->student->student_id,
                    'name' => $this->student->name,
                ];
            }),
        ];
    }
}
