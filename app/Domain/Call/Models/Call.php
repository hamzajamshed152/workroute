<?php

namespace App\Domain\Call\Models;

use App\Domain\Tradie\Models\Tradie;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Call extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'tenant_id',
        'tradie_id',
        'twilio_call_sid',           // Twilio's unique call identifier — used for deduplication
        'caller_number',
        'called_number',             // The Twilio business number that was dialled
        'status',                    // initiated | ringing | forwarded | ai_handling | completed | failed
        'direction',                 // inbound | outbound
        'forwarded_to',
        'forward_status',            // pending | no-answer | completed | failed
        'ai_session_id',
        'duration_seconds',
        'recording_url',
        'metadata',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public function tradie(): BelongsTo
    {
        return $this->belongsTo(Tradie::class);
    }

    public function job(): HasOne
    {
        return $this->hasOne(\App\Domain\Job\Models\Job::class);
    }

    public function wasHandledByAI(): bool
    {
        return $this->status === 'ai_handling' || ! empty($this->ai_session_id);
    }

    public function wasAnswered(): bool
    {
        return $this->forward_status === 'completed';
    }
}
