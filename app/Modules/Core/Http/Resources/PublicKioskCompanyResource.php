<?php

namespace App\Modules\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicKioskCompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'logo' => $this->logo,
            'timezone' => $this->timezone,
            'date_format' => $this->date_format,
        ];
    }
}
