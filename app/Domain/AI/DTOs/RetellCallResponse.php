<?php

namespace App\Domain\AI\DTOs;

// ── Retell response after registering a call ──────────────────────────────────
final readonly class RetellCallResponse
{
    public function __construct(
        public string $retellCallId,
        public string $webSocketUrl,    // Passed to Twilio <Stream> URL
    ) {}
}
