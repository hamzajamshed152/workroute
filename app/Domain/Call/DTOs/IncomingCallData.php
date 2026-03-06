<?php

namespace App\Domain\Call\DTOs;

// ── Incoming call from Twilio webhook ─────────────────────────────────────────
final readonly class IncomingCallData
{
    public function __construct(
        public string  $callSid,
        public string  $callerNumber,
        public string  $calledNumber,   // The Twilio business number
        public string  $callStatus,
        public ?string $tenantId = null,
        public ?string $tradieId = null,
    ) {}

    public static function fromTwilioRequest(\Illuminate\Http\Request $r): self
    {
        return new self(
            callSid:      $r->input('CallSid'),
            callerNumber: $r->input('From'),
            calledNumber: $r->input('To'),
            callStatus:   $r->input('CallStatus'),
        );
    }
}
