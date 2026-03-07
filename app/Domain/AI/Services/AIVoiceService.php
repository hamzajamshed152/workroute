<?php

namespace App\Domain\AI\Services;

use App\Domain\AI\Contracts\AIProviderInterface;
use App\Domain\AI\DTOs\CreateAgentData;
use App\Domain\AI\DTOs\ExtractedJobDetails;
use App\Domain\AI\DTOs\RetellCallResponse;
use App\Domain\Tradie\Models\Tradie;

class AIVoiceService
{
    public function __construct(
        private AIProviderInterface $provider,
    ) {}

    /**
     * Provision a Retell AI agent for a newly onboarded tradie.
     * Each tradie gets their own agent so the AI can use their name and trade.
     */
    public function provisionAgentForTradie(Tradie $tradie): string
    {
        $systemPrompt = $this->buildSystemPrompt($tradie);

        $agentId = $this->provider->createAgent(new CreateAgentData(
            agentName:    "Agent for {$tradie->name}",
            systemPrompt: $systemPrompt,
            voice:        config('services.retell.default_voice', '11labs-Adrian'),
            language:     'en-AU',
            beginMessage: "Hi there! You've reached {$tradie->name}'s trade business. They're currently unavailable but I can help take down your job details. Can I start with your name?",
        ));

        // Persist agent_id on the tradie so we can use it per-call
        $tradie->update(['retell_agent_id' => $agentId]);

        return $agentId;
    }

    /**
     * Register an inbound call with Retell and get the WebSocket URL for Twilio.
     */
    public function initiateCallSession(Tradie $tradie, string $callSid, array $metadata = []): RetellCallResponse
    {
        // Check subscription is active
        if (! $tradie->isSubscriptionActive()) {
            throw new \RuntimeException('Your subscription is inactive. Please update your billing details.');
        }

        // Check AI minutes remaining
        if (! $tradie->hasAIMinutesRemaining()) {
            throw new \RuntimeException(
                "Monthly AI minutes limit reached ({$tradie->ai_minutes_limit} mins). Please upgrade your plan."
            );
        }

        return $this->provider->registerCall($tradie->retell_agent_id, $callSid, [
            'tradie_id' => $tradie->id,
        ]);
    }

    /**
     * Parse the Retell post-call webhook payload into structured job details.
     */
    public function extractJobDetails(array $retellPayload): ExtractedJobDetails
    {
        return ExtractedJobDetails::fromRetellPayload($retellPayload);
    }

    private function buildSystemPrompt(Tradie $tradie): string
    {
        $skills = implode(', ', $tradie->skills ?? ['general trade work']);

        return <<<PROMPT
        You are a professional receptionist for {$tradie->name}, a tradie specialising in {$skills}.
        Your sole purpose is to capture job enquiry details from callers.

        You must collect:
        - Customer full name
        - Customer address (where the job needs to be done)
        - Description of the work required
        - Preferred time/date if they have one

        Be warm, professional, and concise. Do not discuss pricing.
        Do not make any commitments on the tradie's behalf.
        Once you have all details, confirm them back to the caller and let them know
        the tradie will be in touch shortly.

        Respond in Australian English. Keep responses brief.

        After the call, populate custom_analysis_data with:
        - customer_name
        - customer_address
        - job_description
        - skill_required
        - preferred_time
        PROMPT;
    }
}
