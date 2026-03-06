<?php

// namespace App\Domain\Job\Events;

// use App\Domain\Call\Events\DomainEvent;

// class JobCreated extends DomainEvent
// {
//     public function __construct(
//         public readonly string  $jobId,
//         public readonly string $tradieId,
//         public readonly ?string $callId,
//         public readonly string  $source,    // manual | ai | forwarded
//         public readonly string  $status,
//     ) { parent::__construct(); }
// }

// class JobAssigned extends DomainEvent
// {
//     public function __construct(
//         public readonly string $jobId,
//         public readonly string $tradieId,
//     ) { parent::__construct(); }
// }

// class JobStatusChanged extends DomainEvent
// {
//     public function __construct(
//         public readonly string $jobId,
//         public readonly string $tradieId,
//         public readonly string $fromStatus,
//         public readonly string $toStatus,
//     ) { parent::__construct(); }
// }

// class JobCompleted extends DomainEvent
// {
//     public function __construct(
//         public readonly string  $jobId,
//         public readonly string  $tradieId,
//     ) { parent::__construct(); }
// }
