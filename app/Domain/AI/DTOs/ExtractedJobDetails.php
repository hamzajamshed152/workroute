<?php

namespace App\Domain\AI\DTOs;

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
