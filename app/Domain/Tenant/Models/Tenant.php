<?php

namespace App\Domain\Tenant\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'stripe_customer_id',
        'stripe_subscription_id',
        'subscription_status',   // active | trialing | past_due | cancelled
        'subscription_plan',
        'trial_ends_at',
        'features',
    ];

    protected $casts = [
        'features'      => 'array',
        'trial_ends_at' => 'datetime',
    ];

    public function tradies(): HasMany
    {
        return $this->hasMany(\App\Domain\Tradie\Models\Tradie::class);
    }

    public function isSubscriptionActive(): bool
    {
        return in_array($this->subscription_status, ['active', 'trialing']);
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }
}
