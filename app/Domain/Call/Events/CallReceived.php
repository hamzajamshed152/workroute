<?php

namespace App\Domain\Call\Events;

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
