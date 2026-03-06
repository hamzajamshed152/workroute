<?php

namespace App\Domain\Call\Events;

class CallCompleted extends DomainEvent
{
    public function __construct(
        public readonly string  $callId,
        public readonly string  $tradieId,
        public readonly int     $durationSeconds,
        public readonly string  $outcome,  // answered | ai_handled | missed
    ) { parent::__construct(); }
}
