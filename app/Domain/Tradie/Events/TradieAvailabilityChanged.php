<?php

namespace App\Domain\Tradie\Events;

use App\Domain\Call\Events\DomainEvent;

class TradieAvailabilityChanged extends DomainEvent
{
    public function __construct(
        public readonly string $tradieId,
        public readonly bool   $isAvailable,
    ) { parent::__construct(); }
}
