<?php

namespace App\Domain\Call\DTOs;

final readonly class CallRoutingResult
{
    private function __construct(
        public bool    $shouldForward,
        public ?string $tradieId,
        public ?string $tradiePersonalPhone,
        public string  $reason,
    ) {}

    public static function forwardTo(string $tradieId, string $phone): self
    {
        return new self(true, $tradieId, $phone, 'tradie_available');
    }

    public static function handToAI(string $reason = 'no_tradie_available'): self
    {
        return new self(false, null, null, $reason);
    }
}
