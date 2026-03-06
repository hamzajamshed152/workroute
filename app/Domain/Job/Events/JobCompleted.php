<?php

namespace App\Domain\Job\Events;

use App\Domain\Call\Events\DomainEvent;

class JobCompleted extends DomainEvent
{
    public function __construct(
        public readonly string  $jobId,
        public readonly string  $tradieId,
    ) { parent::__construct(); }
}
