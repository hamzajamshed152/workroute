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
