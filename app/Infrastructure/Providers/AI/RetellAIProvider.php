<?php

namespace App\Infrastructure\Providers\AI;

use App\Domain\AI\Contracts\AIProviderInterface;
use App\Domain\AI\DTOs\CreateAgentData;
use App\Domain\AI\DTOs\RetellCallResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RetellAIProvider implements AIProviderInterface
{
    private string $baseUrl = 'https://api.retellai.com';

    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . config('services.retell.api_key'),
            'Content-Type'  => 'application/json',
        ];
    }

    public function createAgent(CreateAgentData $data): string
    {
        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/create-agent", [
                'agent_name'     => $data->agentName,
                'response_engine'=> [
                    'type'          => 'retell-llm',
                    'llm_id'        => $this->createLLM($data),
                ],
                'voice_id'       => $data->voice,
                'language'       => $data->language,
                'begin_message'  => $data->beginMessage,
            ]);

        throw_unless($response->successful(), \RuntimeException::class,
            'Failed to create Retell agent: ' . $response->body());

        return $response->json('agent_id');
    }

    public function updateAgent(string $agentId, CreateAgentData $data): void
    {
        Http::withHeaders($this->headers())
            ->patch("{$this->baseUrl}/update-agent/{$agentId}", [
                'begin_message' => $data->beginMessage,
            ])
            ->throw();
    }

    public function deleteAgent(string $agentId): void
    {
        Http::withHeaders($this->headers())
            ->delete("{$this->baseUrl}/delete-agent/{$agentId}")
            ->throw();
    }

    /**
     * Register the inbound call with Retell.
     * Returns a WebSocket URL that Twilio streams the call audio to.
     */
    // public function registerCall(string $agentId, string $callSid, array $metadata = []): RetellCallResponse
    // {
    //     $response = Http::withHeaders($this->headers())
    //         ->post("{$this->baseUrl}/v2/register-phone-call", [
    //             'agent_id'                 => $agentId,
    //             'audio_websocket_protocol' => 'twilio',
    //             'audio_encoding'           => 'mulaw',
    //             'sample_rate'              => 8000,
    //             'metadata'                 => array_merge($metadata, ['twilio_call_sid' => $callSid]),
    //         ]);

    //     throw_unless($response->successful(), \RuntimeException::class,
    //         'Failed to register call with Retell: ' . $response->body());

    //     Log::info('Retell call registered', [
    //         'call_id'     => $response->json('call_id'),
    //         'call_status' => $response->json('call_status'),
    //         'full_body'   => $response->json(),
    //     ]);

    //     $callId = $response->json('call_id');

    //     // WebSocket URL is constructed from call_id — not returned directly by the API
    //     $webSocketUrl = "wss://api.retellai.com/audio-websocket/{$callId}?api_key=" . config('services.retell.api_key');

    //     return new RetellCallResponse(
    //         retellCallId: $callId,
    //         webSocketUrl: $webSocketUrl,
    //     );
    // }

    public function getSipUri(): string
    {
        return config('services.retell_sip_uri');
    }

    /**
     * Validate that the webhook came from Retell using their API key as the shared secret.
     */
    public function validateWebhookSignature(Request $request): void
    {
        // Retell sends their API key in the Authorization header
        $providedKey = $request->header('Authorization');
        $expectedKey = 'Bearer ' . config('services.retell.api_key');

        throw_unless(
            hash_equals($expectedKey, $providedKey ?? ''),
            \Symfony\Component\HttpKernel\Exception\HttpException::class,
            403,
        );
    }

    /**
     * Create a Retell LLM and return its ID — required before creating an agent.
     */
    private function createLLM(CreateAgentData $data): string
    {
        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/create-retell-llm", [
                'general_prompt'   => $data->systemPrompt,
                'model'            => 'gpt-4o',
                'general_tools'    => [],
            ]);

        $response->throw();

        return $response->json('llm_id');
    }
}
