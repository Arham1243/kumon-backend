<?php

namespace App\Modules\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
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
            'name' => $this->name,
            'status' => (bool) $this->status,
            'parent_1_mobile' => $this->parent_1_mobile,
            'parent_2_mobile' => $this->parent_2_mobile,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'maths' => (bool) $this->maths,
            'reading' => (bool) $this->reading,
            'tuition_fee' => $this->tuition_fee !== null ? (float) $this->tuition_fee : null,
            'card_uid' => $this->card_uid,
            'created_at' => $this->created_at,
        ];
    }
}
