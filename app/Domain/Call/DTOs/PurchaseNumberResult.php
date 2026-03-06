<?php

namespace App\Domain\Call\DTOs;

// ── Result of purchasing a Twilio number ──────────────────────────────────────
final readonly class PurchaseNumberResult
{
    public function __construct(
        public string $phoneNumber,     // e.g. +16175551234
        public string $numberSid,       // Twilio SID
        public string $friendlyName,
    ) {}
}
