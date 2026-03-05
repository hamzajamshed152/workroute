<?php

namespace App\Domain\Tradie\Models;

use App\Domain\Tenant\Models\Tenant;
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
        'tenant_id',
        'name',
        'email',
        'password',
        'personal_phone',       // The tradie's real phone number — calls are forwarded here
        'business_number',      // Twilio number assigned to this tradie
        'twilio_number_sid',    // Twilio SID for the purchased number
        'is_available',
        'role',                 // admin | tradie | dispatcher
        'skills',
        'timezone',
        'notification_preferences',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_available'               => 'boolean',
        'skills'                     => 'array',
        'notification_preferences'   => 'array',
        'email_verified_at'          => 'datetime',
        'password'                   => 'hashed',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

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
}
