<?php

namespace App\Domain\AI\Contracts;

use App\Domain\AI\DTOs\CreateAgentData;
use App\Domain\AI\DTOs\RetellCallResponse;

interface AIProviderInterface
{
    /**
     * Register a new Retell AI agent scoped to a specific tradie/tenant.
     * Called during onboarding so each tradie has their own agent configuration.
     */
    public function createAgent(CreateAgentData $data): string; // returns agent_id

    /**
     * Update the system prompt / configuration of an existing agent.
     */
    public function updateAgent(string $agentId, CreateAgentData $data): void;

    /**
     * Delete an agent when a tradie is offboarded.
     */
    public function deleteAgent(string $agentId): void;

    /**
     * Register an inbound call with Retell to get a WebSocket URL.
     * This URL is fed back to Twilio via TwiML <Stream> to connect the call to the AI.
     */
    public function registerCall(string $agentId, string $callSid, array $metadata = []): RetellCallResponse;


    public function getSipUri(string $agentId): string;

    /**
     * Validate that an inbound webhook genuinely came from Retell.
     * Must throw an exception if invalid.
     */
    public function validateWebhookSignature(\Illuminate\Http\Request $request): void;
}
