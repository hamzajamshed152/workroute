<?php

namespace App\Domain\Call\Contracts;

use App\Domain\Call\DTOs\IncomingCallData;
use App\Domain\Call\DTOs\PurchaseNumberResult;
use App\Domain\Call\DTOs\TwimlResponse;
use Illuminate\Http\Request;

interface CallProviderInterface
{
    /**
     * Parse an inbound webhook request into a normalised DTO.
     * Called on every inbound call webhook from Twilio.
     */
    public function parseIncoming(Request $request): IncomingCallData;

    /**
     * Generate TwiML that forwards the call to a phone number.
     * Used when a tradie is available.
     */
    public function buildForwardResponse(string $toNumber, string $callbackUrl): TwimlResponse;

    /**
     * Generate TwiML that hands the call to Retell AI.
     * Used when no tradie is available (or tradie doesn't answer).
     */
    public function buildAIHandoffResponse(string $retellWebsocketUrl): TwimlResponse;

    /**
     * Generate TwiML that plays a status callback message.
     * Triggered by Twilio's statusCallback when a forwarded call is not answered.
     */
    public function buildNoAnswerFallback(string $aiHandlerUrl): TwimlResponse;

    /**
     * Purchase a new phone number in the given area code.
     * Called during tradie signup/onboarding.
     */
    public function purchaseNumber(string $areaCode, string $friendlyName): PurchaseNumberResult;

    /**
     * Release a previously purchased number back to the pool.
     * Called when a tradie subscription is cancelled.
     */
    public function releaseNumber(string $numberSid): void;

    /**
     * Configure the webhook URLs for an owned number.
     * Called after purchasing a number so Twilio knows where to send events.
     */
    public function configureNumberWebhooks(string $numberSid, string $voiceUrl, string $statusCallbackUrl): void;

    /**
     * Validate that a webhook request genuinely came from this provider.
     * Must throw an exception if validation fails.
     */
    public function validateWebhookSignature(Request $request): void;
}
