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

// ─────────────────────────────────────────────────────────────────────────────

class CallReceived extends DomainEvent
{
    public function __construct(
        public readonly string  $callId,
        public readonly string  $tradieId,
        public readonly string  $callSid,
        public readonly string  $callerNumber,
        public readonly string  $calledNumber,
    ) { parent::__construct(); }
}

class CallForwardedToTradie extends DomainEvent
{
    public function __construct(
        public readonly string $callId,
        public readonly string $tradieId,
        public readonly string $forwardedTo,
    ) { parent::__construct(); }
}

class CallNotAnswered extends DomainEvent
{
    // Twilio statusCallback fires this — triggers AI handoff
    public function __construct(
        public readonly string $callId,
        public readonly string $tradieId,
        public readonly string $callSid,
        public readonly string $dialStatus,  // no-answer | busy | failed
    ) { parent::__construct(); }
}

class CallHandedToAI extends DomainEvent
{
    public function __construct(
        public readonly string $callId,
        public readonly string $tradieId,
        public readonly string $reason,  // no_tradie_available | no_answer
    ) { parent::__construct(); }
}

class CallCompleted extends DomainEvent
{
    public function __construct(
        public readonly string  $callId,
        public readonly string  $tradieId,
        public readonly int     $durationSeconds,
        public readonly string  $outcome,  // answered | ai_handled | missed
    ) { parent::__construct(); }
}
