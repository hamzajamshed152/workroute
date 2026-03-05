<?php

namespace App\Application\Pipelines;

use App\Domain\AI\Services\AIVoiceService;
use App\Domain\Call\DTOs\IncomingCallData;
use App\Domain\Call\DTOs\TwimlResponse;
use App\Domain\Call\Events\CallForwardedToTradie;
use App\Domain\Call\Events\CallHandedToAI;
use App\Domain\Call\Events\CallReceived;
use App\Domain\Call\Models\Call;
use App\Domain\Call\Services\CallRoutingService;
use App\Domain\Job\Services\JobService;
use App\Domain\Tradie\Repositories\TradieRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IncomingCallPipeline
{
    public function __construct(
        private CallRoutingService        $routing,
        private AIVoiceService            $aiVoice,
        private JobService                $jobService,
        private TradieRepositoryInterface $tradies,
    ) {}

    /**
     * Entry point for every inbound call.
     *
     * Returns TwiML that Twilio executes immediately.
     * All side-effects (job creation, events) happen here or in queued listeners.
     */
    public function handle(IncomingCallData $data): TwimlResponse
    {
        // Identify the tradie from the dialled number and resolve tenant
        $tradie = $this->tradies->findByBusinessNumber($data->calledNumber);

        if (! $tradie) {
            Log::warning('IncomingCallPipeline: no tradie found for number', [
                'called_number' => $data->calledNumber,
            ]);
            return $this->buildGenericVoicemail();
        }

        // Persist the call record immediately for idempotency
        $call = $this->persistCall($data, $tradie->id);

        event(new CallReceived($call->id, $tradie->id, $data->callSid, $data->callerNumber, $data->calledNumber));

        // Route the call
        $routingResult = $this->routing->routeIncomingCall($data);

        if ($routingResult->shouldForward) {
            return $this->handleForward($call, $tradie, $routingResult->tradiePersonalPhone);
        }

        return $this->handleAIHandoff($call, $tradie);
    }

    /**
     * Forward path: tradie is available.
     * Twilio will ring the tradie's personal phone. If they don't answer,
     * Twilio fires the statusCallback which triggers handleNoAnswer().
     */
    private function handleForward(Call $call, $tradie, string $personalPhone): TwimlResponse
    {
        $call->update([
            'status'       => 'forwarded',
            'forwarded_to' => $personalPhone,
            'tradie_id'    => $tradie->id,
        ]);

        event(new CallForwardedToTradie($call->id, $tradie->id, $personalPhone));

        // Job is created immediately in 'assigned' state
        // If the tradie doesn't answer, the job will be updated via the status callback
        $this->jobService->createFromForwardedCall(
            $call->id,
            $tradie->id,
            $call->caller_number,
        );

        return $this->routing->buildForwardTwiml($call, $personalPhone, config('app.url'));
    }

    /**
     * AI handoff path: no tradie available.
     * Registers the call with Retell and returns the WebSocket TwiML.
     */
    private function handleAIHandoff(Call $call, $tradie): TwimlResponse
    {
        $agentId = $tradie->retell_agent_id;

        $retellResponse = $this->aiVoice->initiateCallSession($tradie, $call->callSid);

        $call->update([
            'status'         => 'ai_handling',
            'ai_session_id'  => $retellResponse->retellCallId,
        ]);

        event(new CallHandedToAI($call->id, $tradie->id, 'tradie_unavailable'));

        return $this->routing->buildAIHandoffTwiml($retellResponse->webSocketUrl);
    }

    /**
     * Called from the status callback webhook when a forwarded call is not answered.
     * Re-routes to AI to capture the job details.
     */
    public function handleNoAnswer(Call $call, string $dialStatus): TwimlResponse
    {
        $tradie = $this->tradies->findById($call->tradie_id);

        // Release the tradie back to available — they didn't pick up
        // This is handled in the CallNotAnswered event listener, not here

        event(new \App\Domain\Call\Events\CallNotAnswered(
            $call->id,
            $call->tradie_id,
            $call->twilio_call_sid,
            $dialStatus,
        ));

        return $this->handleAIHandoff($call, $tradie);
    }

    private function persistCall(IncomingCallData $data, string $tradieId): Call
    {
        // Idempotent — if Twilio retries the webhook, we don't create a duplicate
        return Call::firstOrCreate(
            ['twilio_call_sid' => $data->callSid],
            [
                'tradie_id'     => $tradieId,
                'caller_number' => $data->callerNumber,
                'called_number' => $data->calledNumber,
                'status'        => 'initiated',
                'direction'     => 'inbound',
                'started_at'    => now(),
            ],
        );
    }

    private function buildGenericVoicemail(): TwimlResponse
    {
        // Plain TwiML — no Twilio SDK needed for this simple case
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
             . '<Response><Say>Sorry, this number is not currently in service. Please try again later.</Say></Response>';

        return new TwimlResponse($xml);
    }
}
