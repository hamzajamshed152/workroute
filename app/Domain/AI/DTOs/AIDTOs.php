<?php

namespace App\Domain\AI\DTOs;

// ── Data needed to create a Retell AI agent ───────────────────────────────────
final readonly class CreateAgentData
{
    public function __construct(
        public string  $agentName,
        public string  $systemPrompt,
        public string  $voice,           // e.g. "11labs-Adrian"
        public string  $language = 'en-AU',
        public ?string $beginMessage = null,
    ) {}
}

// ── Retell response after registering a call ──────────────────────────────────
final readonly class RetellCallResponse
{
    public function __construct(
        public string $retellCallId,
        public string $webSocketUrl,    // Passed to Twilio <Stream> URL
    ) {}
}

// ── Job details extracted by the AI after the call ────────────────────────────
final readonly class ExtractedJobDetails
{
    public function __construct(
        public ?string $customerName,
        public ?string $customerAddress,
        public ?string $description,
        public ?string $skillRequired,
        public ?string $preferredTime,
        public array   $rawTranscript = [],
    ) {}

    public static function fromRetellPayload(array $payload): self
    {
        $custom = $payload['call_analysis']['custom_analysis_data'] ?? [];

        return new self(
            customerName:    $custom['customer_name'] ?? null,
            customerAddress: $custom['customer_address'] ?? null,
            description:     $custom['job_description'] ?? null,
            skillRequired:   $custom['skill_required'] ?? null,
            preferredTime:   $custom['preferred_time'] ?? null,
            rawTranscript:   $payload['transcript_object'] ?? [],
        );
    }
}
