<?php

namespace App\Domain\Call\Events;

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
