<?php

namespace App\Domain\Job\Models;

use App\Domain\Call\Models\Call;
use App\Domain\Tradie\Models\Tradie;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Job extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'tenant_id',
        'call_id',
        'tradie_id',
        'status',           // pending | assigned | ai_created | in_progress | completed | cancelled
        'source',           // manual | ai | forwarded
        'customer_name',
        'customer_phone',
        'customer_address',
        'description',
        'skill_required',
        'notes',
        'ai_transcript',
        'scheduled_at',
        'assigned_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'metadata',
    ];

    protected $casts = [
        'metadata'     => 'array',
        'scheduled_at' => 'datetime',
        'assigned_at'  => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function tradie(): BelongsTo
    {
        return $this->belongsTo(Tradie::class);
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    // ── Status helpers ─────────────────────────────────────────────────────────

    public function isPending(): bool     { return $this->status === 'pending'; }
    public function isAssigned(): bool    { return $this->status === 'assigned'; }
    public function isAICreated(): bool   { return $this->status === 'ai_created'; }
    public function isInProgress(): bool  { return $this->status === 'in_progress'; }
    public function isCompleted(): bool   { return $this->status === 'completed'; }
    public function isCancelled(): bool   { return $this->status === 'cancelled'; }

    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = match ($this->status) {
            'pending'     => ['assigned', 'cancelled'],
            'ai_created'  => ['assigned', 'cancelled'],
            'assigned'    => ['in_progress', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
            default       => [],
        };

        return in_array($newStatus, $allowed);
    }
}
