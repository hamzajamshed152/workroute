<?php

namespace App\Domain\Call\DTOs;

// ── TwiML response wrapper ─────────────────────────────────────────────────────
final readonly class TwimlResponse
{
    public function __construct(
        public string $xml,
        public int    $httpStatus = 200,
    ) {}
}
