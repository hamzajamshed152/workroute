<?php

namespace App\Domain\Tradie\Events;

use App\Domain\Call\Events\DomainEvent;

class TradieBusinessNumberAssigned extends DomainEvent
{
    public function __construct(
        public readonly string $tradieId,
        public readonly string $businessNumber,
        public readonly string $numberSid,
    ) { parent::__construct(); }
}
