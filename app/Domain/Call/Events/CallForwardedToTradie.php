<?php

namespace App\Domain\Call\Events;

class CallForwardedToTradie extends DomainEvent
{
    public function __construct(
        public readonly string $callId,
        public readonly string $tradieId,
        public readonly string $forwardedTo,
    ) { parent::__construct(); }
}
