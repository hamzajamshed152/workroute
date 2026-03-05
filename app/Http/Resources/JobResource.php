<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'tenant_id'           => $this->tenant_id,
            'call_id'             => $this->call_id,
            'tradie_id'           => $this->tradie_id,
            'status'              => $this->status,
            'source'              => $this->source,
            'customer_name'       => $this->customer_name,
            'customer_phone'      => $this->customer_phone,
            'customer_address'    => $this->customer_address,
            'description'         => $this->description,
            'skill_required'      => $this->skill_required,
            'notes'               => $this->notes,
            'scheduled_at'        => $this->scheduled_at?->toISOString(),
            'assigned_at'         => $this->assigned_at?->toISOString(),
            'completed_at'        => $this->completed_at?->toISOString(),
            'cancelled_at'        => $this->cancelled_at?->toISOString(),
            'cancellation_reason' => $this->cancellation_reason,
            'created_at'          => $this->created_at->toISOString(),

            // Eager loaded relationships — only included when loaded
            'tradie' => $this->whenLoaded('tradie', fn() => [
                'id'   => $this->tradie->id,
                'name' => $this->tradie->name,
            ]),
            'call' => $this->whenLoaded('call', fn() => [
                'id'            => $this->call->id,
                'caller_number' => $this->call->caller_number,
                'duration'      => $this->call->duration_seconds,
            ]),
        ];
    }
}
