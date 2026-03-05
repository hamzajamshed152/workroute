<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TradieResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'email'           => $this->email,
            'personal_phone'  => $this->personal_phone,
            'business_number' => $this->business_number,
            'is_available'    => $this->is_available,
            'role'            => $this->role,
            'skills'          => $this->skills ?? [],
            'timezone'        => $this->timezone,
            'tenant_id'       => $this->tenant_id,
            'created_at'      => $this->created_at->toISOString(),
        ];
    }
}
