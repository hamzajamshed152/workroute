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

// ── Result of routing a call ───────────────────────────────────────────────────
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

// ── TwiML response wrapper ─────────────────────────────────────────────────────
final readonly class TwimlResponse
{
    public function __construct(
        public string $xml,
        public int    $httpStatus = 200,
    ) {}
}

// ── Result of purchasing a Twilio number ──────────────────────────────────────
final readonly class PurchaseNumberResult
{
    public function __construct(
        public string $phoneNumber,     // e.g. +16175551234
        public string $numberSid,       // Twilio SID
        public string $friendlyName,
    ) {}
}
