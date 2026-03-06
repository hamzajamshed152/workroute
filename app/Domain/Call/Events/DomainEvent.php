<?php

namespace App\Domain\Call\Events;

// ── Base event all domain events extend ──────────────────────────────────────
abstract class DomainEvent
{
    public readonly string $eventId;
    public readonly string $occurredAt;

    public function __construct()
    {
        $this->eventId   = (string) \Illuminate\Support\Str::uuid();
        $this->occurredAt = now()->toISOString();
    }
}
