<?php

namespace App\Http\Controllers\Webhook;

use App\Application\Pipelines\IncomingCallPipeline;
use App\Domain\AI\Services\AIVoiceService;
use App\Domain\Call\Contracts\CallProviderInterface;
use App\Domain\Call\DTOs\IncomingCallData;
use App\Domain\Call\Models\Call;
use App\Domain\Job\Services\JobService;
use App\Domain\Tradie\Services\TradieAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class CallWebhookController extends Controller
{
    public function __construct(
        private CallProviderInterface $callProvider,
        private IncomingCallPipeline  $pipeline,
    ) {}

    /**
     * POST /webhooks/call/inbound
     *
     * Called by Twilio on every inbound call.
     * Must respond with valid TwiML within ~5 seconds or Twilio gives up.
     */
    public function inbound(Request $request): Response
    {
        $this->callProvider->validateWebhookSignature($request);

        $data     = $this->callProvider->parseIncoming($request);
        $twiml    = $this->pipeline->handle($data);

        return response($twiml->xml, $twiml->httpStatus)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * POST /webhooks/call/status/{callId}
     *
     * Twilio fires this when a <Dial> completes — answered, no-answer, busy, failed.
     * If the tradie didn't answer, we hand off to AI here.
     */
    // public function status(Request $request): Response
    // {
    //     // $this->callProvider->validateWebhookSignature($request);
    //     $callSid = $request->input('CallSid');
    //     $dialStatus = $request->input('DialCallStatus'); // no-answer | completed | busy | failed

    //     $call = Call::findOrFail($callSid);

    //     if (in_array($dialStatus, ['no-answer', 'busy', 'failed'])) {
    //         // Tradie didn't pick up — route to AI
    //         $twiml = $this->pipeline->handleNoAnswer($call, $dialStatus);

    //         return response($twiml->xml, 200)
    //             ->header('Content-Type', 'application/xml');
    //     }

    //     // Call was answered — mark completed
    //     $call->update([
    //         'forward_status'   => $dialStatus,
    //         'duration_seconds' => $request->input('DialCallDuration', 0),
    //         'status'           => 'completed',
    //         'ended_at'         => now(),
    //     ]);

    //     return response('<Response></Response>', 200)
    //         ->header('Content-Type', 'application/xml');
    // }
    public function status(Request $request): Response
    {
        $callSid    = $request->input('CallSid');
        $dialStatus = $request->input('DialCallStatus');

        // Find by twilio_call_sid column, not primary key
        $call = Call::where('twilio_call_sid', $callSid)->first();

        if (! $call) {
            // Return empty TwiML — don't crash, Twilio needs a valid response
            return response('<Response></Response>', 200)
                ->header('Content-Type', 'application/xml');
        }

        if (in_array($dialStatus, ['no-answer', 'busy', 'failed'])) {
            $twiml = $this->pipeline->handleNoAnswer($call, $dialStatus);

            return response($twiml->xml, 200)
                ->header('Content-Type', 'application/xml');
        }

        // Call was answered — mark completed
        $call->update([
            'forward_status'   => $dialStatus,
            'duration_seconds' => $request->input('DialCallDuration', 0),
            'status'           => 'completed',
            'ended_at'         => now(),
        ]);

        return response('<Response></Response>', 200)
            ->header('Content-Type', 'application/xml');
    }
}
