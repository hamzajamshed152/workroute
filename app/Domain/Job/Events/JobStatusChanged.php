<?php

namespace App\Domain\Job\Events;

use App\Domain\Call\Events\DomainEvent;

class JobStatusChanged extends DomainEvent
{
    public function __construct(
        public readonly string $jobId,
        public readonly string $tradieId,
        public readonly string $fromStatus,
        public readonly string $toStatus,
    ) { parent::__construct(); }
}
