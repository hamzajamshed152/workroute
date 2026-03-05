<?php

namespace App\Domain\Call\Services;

use App\Domain\Call\Contracts\CallProviderInterface;
use App\Domain\Call\DTOs\CallRoutingResult;
use App\Domain\Call\DTOs\IncomingCallData;
use App\Domain\Call\DTOs\TwimlResponse;
use App\Domain\Call\Models\Call;
use App\Domain\Tradie\Repositories\TradieRepositoryInterface;
use App\Domain\Tradie\Services\TradieAvailabilityService;

class CallRoutingService
{
    public function __construct(
        private CallProviderInterface      $provider,
        private TradieRepositoryInterface  $tradies,
        private TradieAvailabilityService  $availability,
    ) {}

    /**
     * Core routing decision: forward to tradie or hand to AI.
     *
     * The tradie is looked up by the called number (the Twilio business number).
     * If they're available, we forward. If not, we immediately hand to AI.
     */
    public function routeIncomingCall(IncomingCallData $data): CallRoutingResult
    {
        // Identify which tradie owns this business number
        $tradie = $this->tradies->findByBusinessNumber($data->calledNumber);

        if (! $tradie) {
            // Number not mapped to any tradie — shouldn't happen in production
            return CallRoutingResult::handToAI('unknown_number');
        }

        // Atomically claim the tradie if available
        $claimedTradie = $this->availability->claimAvailableTradie($tradie->tenant_id);

        if ($claimedTradie && $claimedTradie->id === $tradie->id) {
            return CallRoutingResult::forwardTo($tradie->id, $tradie->personal_phone);
        }

        return CallRoutingResult::handToAI('tradie_unavailable');
    }

    /**
     * Build the TwiML response to forward the call.
     * The statusCallback URL lets Twilio notify us if the tradie doesn't answer.
     */
    public function buildForwardTwiml(Call $call, string $personalPhone, string $appBaseUrl): TwimlResponse
    {
        $statusCallbackUrl = "{$appBaseUrl}/webhooks/call/status/{$call->id}";
        return $this->provider->buildForwardResponse($personalPhone, $statusCallbackUrl);
    }

    /**
     * Build the TwiML response to hand control to Retell AI.
     */
    public function buildAIHandoffTwiml(string $retellWebsocketUrl): TwimlResponse
    {
        return $this->provider->buildAIHandoffResponse($retellWebsocketUrl);
    }
}
