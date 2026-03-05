<?php

namespace App\Infrastructure\Providers\Call;

use App\Domain\Call\Contracts\CallProviderInterface;
use App\Domain\Call\DTOs\IncomingCallData;
use App\Domain\Call\DTOs\PurchaseNumberResult;
use App\Domain\Call\DTOs\TwimlResponse;
use Illuminate\Http\Request;
use Twilio\Rest\Client as TwilioClient;
use Twilio\Security\RequestValidator;
use Twilio\TwiML\VoiceResponse;

class TwilioCallProvider implements CallProviderInterface
{
    private TwilioClient $client;

    public function __construct()
    {
        $this->client = new TwilioClient(
            config('services.twilio.sid'),
            config('services.twilio.token'),
        );
    }

    public function parseIncoming(Request $request): IncomingCallData
    {
        return IncomingCallData::fromTwilioRequest($request);
    }

    /**
     * Forward the call to the tradie's personal phone.
     * The <Dial> verb with statusCallbackEvent lets us catch no-answer.
     */
    public function buildForwardResponse(string $toNumber, string $callbackUrl): TwimlResponse
    {
        $response = new VoiceResponse();

        $dial = $response->dial('', [
            'timeout'               => 20,         // Ring for 20 seconds before giving up
            'action'                => $callbackUrl, // Called when dial completes (no-answer, busy, etc.)
            'method'                => 'POST',
        ]);

        $dial->number($toNumber, [
            'statusCallbackEvent' => 'answered ringing completed',
            'statusCallbackMethod'=> 'POST',
            'statusCallback'      => $callbackUrl,
        ]);

        return new TwimlResponse((string) $response);
    }

    /**
     * Hand the call to Retell AI via Twilio's <Connect><Stream> verb.
     * The WebSocket URL is provided by Retell after registering the call.
     */
    public function buildAIHandoffResponse(string $retellWebsocketUrl): TwimlResponse
    {
        $response = new VoiceResponse();

        $connect = $response->connect();
        $connect->stream([
            'url'   => $retellWebsocketUrl,
            'track' => 'both_tracks',   // Send both caller and agent audio to Retell
        ]);

        return new TwimlResponse((string) $response);
    }

    /**
     * Fallback TwiML when a forwarded call is not answered.
     * Redirects to the AI handler endpoint.
     */
    public function buildNoAnswerFallback(string $aiHandlerUrl): TwimlResponse
    {
        $response = new VoiceResponse();
        $response->redirect($aiHandlerUrl, ['method' => 'POST']);

        return new TwimlResponse((string) $response);
    }

    public function purchaseNumber(string $areaCode, string $friendlyName): PurchaseNumberResult
    {
        // Search for an available number in the given area code
        $available = $this->client->availablePhoneNumbers('AU')
            ->local
            ->read(['areaCode' => $areaCode], 1);

        if (empty($available)) {
            // Fallback — search without area code restriction
            $available = $this->client->availablePhoneNumbers('AU')
                ->local
                ->read([], 1);
        }

        throw_if(empty($available), \RuntimeException::class, 'No available numbers found.');

        $purchased = $this->client->incomingPhoneNumbers->create([
            'phoneNumber'  => $available[0]->phoneNumber,
            'friendlyName' => $friendlyName,
        ]);

        return new PurchaseNumberResult(
            phoneNumber:  $purchased->phoneNumber,
            numberSid:    $purchased->sid,
            friendlyName: $purchased->friendlyName,
        );
    }

    public function releaseNumber(string $numberSid): void
    {
        $this->client->incomingPhoneNumbers($numberSid)->delete();
    }

    public function configureNumberWebhooks(string $numberSid, string $voiceUrl, string $statusCallbackUrl): void
    {
        $this->client->incomingPhoneNumbers($numberSid)->update([
            'voiceUrl'            => $voiceUrl,
            'voiceMethod'         => 'POST',
            'statusCallback'      => $statusCallbackUrl,
            'statusCallbackMethod'=> 'POST',
        ]);
    }

    /**
     * Validate the Twilio webhook signature to prevent spoofed requests.
     * This MUST be called before processing any webhook payload.
     */
    public function validateWebhookSignature(Request $request): void
    {
        $validator = new RequestValidator(config('services.twilio.token'));

        $isValid = $validator->validate(
            $request->header('X-Twilio-Signature'),
            $request->fullUrl(),
            $request->post(),
        );

        throw_unless($isValid, \Symfony\Component\HttpKernel\Exception\HttpException::class, 403);
    }
}
