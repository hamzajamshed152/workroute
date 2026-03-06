<?php

namespace App\Domain\Tradie\Events;

use App\Domain\Call\Events\DomainEvent;

class TradieRegistered extends DomainEvent
{
    public function __construct(
        public readonly string $tradieId,
        public readonly string $email,
    ) { parent::__construct(); }
}
