<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CallResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'caller_number'   => $this->caller_number,
            'called_number'   => $this->called_number,
            'status'          => $this->status,
            'direction'       => $this->direction,
            'forwarded_to'    => $this->forwarded_to,
            'forward_status'  => $this->forward_status,
            'duration_seconds'=> $this->duration_seconds,
            'was_ai_handled'  => $this->wasHandledByAI(),
            'started_at'      => $this->started_at?->toISOString(),
            'ended_at'        => $this->ended_at?->toISOString(),
            'created_at'      => $this->created_at->toISOString(),

            'job' => $this->whenLoaded('job', fn() => new JobResource($this->job)),
        ];
    }
}
