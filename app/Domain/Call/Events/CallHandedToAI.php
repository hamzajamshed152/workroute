<?php

namespace App\Domain\Call\Events;

class CallHandedToAI extends DomainEvent
{
    public function __construct(
        public readonly string $callId,
        public readonly string $tradieId,
        public readonly string $reason,  // no_tradie_available | no_answer
    ) { parent::__construct(); }
}
