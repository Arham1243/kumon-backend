<?php

namespace App\Modules\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'student_id' => $this->student_id,
            'date' => $this->date?->format('Y-m-d'),
            'check_in_time' => $this->formatTime($this->check_in_time),
            'check_out_time' => $this->formatTime($this->check_out_time),
            'created_at' => $this->created_at,
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'uuid' => $this->student->uuid,
                'student_id' => $this->student->student_id,
                'name' => $this->student->name,
            ]),
        ];
    }

    private function formatTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value) && strlen($value) >= 5) {
            return substr($value, 0, 5);
        }

        return (string) $value;
    }
}
