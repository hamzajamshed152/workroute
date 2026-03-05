<?php

namespace App\Domain\Tradie\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Tradie extends Authenticatable
{
    use HasApiTokens, HasUuids, Notifiable;

    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'personal_phone',
        'business_number',
        'twilio_number_sid',
        'retell_agent_id',
        'is_available',
        'skills',
        'timezone',
        'notification_preferences',
        // Subscription
        'stripe_customer_id',
        'stripe_subscription_id',
        'subscription_status',
        'subscription_plan',
        'trial_ends_at',
        // Usage
        'ai_minutes_used',
        'ai_minutes_limit',
        'usage_reset_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'skills'                   => 'array',
        'notification_preferences' => 'array',
        'is_available'             => 'boolean',
        'trial_ends_at'            => 'datetime',
        'usage_reset_at'           => 'datetime',
        'email_verified_at'        => 'datetime',
    ];

    public function jobs(): HasMany
    {
        return $this->hasMany(\App\Domain\Job\Models\Job::class);
    }

    public function calls(): HasMany
    {
        return $this->hasMany(\App\Domain\Call\Models\Call::class);
    }

    public function isAvailable(): bool
    {
        return $this->is_available;
    }

    public function markAvailable(): void
    {
        $this->update(['is_available' => true]);
    }

    public function markUnavailable(): void
    {
        $this->update(['is_available' => false]);
    }

    public function hasBusinessNumber(): bool
    {
        return ! empty($this->business_number);
    }

    // --- Subscription checks ---

    public function isSubscriptionActive(): bool
    {
        return in_array($this->subscription_status, ['active', 'trialing']);
    }

    public function isTrialing(): bool
    {
        return $this->subscription_status === 'trialing'
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    // --- Usage / limits ---

    public function hasAIMinutesRemaining(): bool
    {
        return $this->ai_minutes_used < $this->ai_minutes_limit;
    }

    public function remainingAIMinutes(): int
    {
        return max(0, $this->ai_minutes_limit - $this->ai_minutes_used);
    }

    public function incrementAIMinutes(int $minutes): void
    {
        $this->increment('ai_minutes_used', $minutes);
    }

    // Resets usage on the 1st of each month — called by a scheduled command
    public function resetMonthlyUsage(): void
    {
        $this->update([
            'ai_minutes_used' => 0,
            'usage_reset_at'  => now(),
        ]);
    }
}
