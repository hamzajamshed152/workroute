<?php

namespace App\Http\Controllers\Webhook;

use App\Domain\AI\Contracts\AIProviderInterface;
use App\Domain\AI\Services\AIVoiceService;
use App\Domain\Call\Models\Call;
use App\Domain\Job\Services\JobService;
use App\Domain\Tradie\Models\Tradie;
use App\Domain\Tradie\Services\TradieAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class RetellWebhookController extends Controller
{
    public function __construct(
        private AIProviderInterface        $aiProvider,
        private AIVoiceService             $aiVoiceService,
        private JobService                 $jobService,
        private TradieAvailabilityService  $availability,
    ) {}

    /**
     * POST /webhooks/retell/call-ended
     *
     * Retell fires this when the AI call finishes.
     * This is where we extract job details and create the job record.
     *
     * Retell events: call_started | call_ended | call_analyzed
     */
    public function handle(Request $request): JsonResponse
    {
        // $this->aiProvider->validateWebhookSignature($request);
        // Skip signature check in local/testing environment
        if (! config('services.retell.skip_signature_check')) {
            $this->aiProvider->validateWebhookSignature($request);
        }


        $event   = $request->input('event');
        $payload = $request->input('call', []);

        Log::info('Retell webhook received', ['event' => $event, 'call_id' => $payload['call_id'] ?? null]);

        return match ($event) {
            'call_ended'    => $this->onCallEnded($payload),
            'call_analyzed' => $this->onCallAnalyzed($payload),
            default         => response()->json(['status' => 'ignored']),
        };
    }

    /**
     * Fired as soon as the call ends. Release the tradie back to available if they were on the call.
     */
    // private function onCallEnded(array $payload): JsonResponse
    // {
    //     $retellCallId = $payload['call_id'];

    //     $call = Call::where('ai_session_id', $retellCallId)->first();

    //     if ($call && $call->tradie_id) {
    //         // If this was a no-answer scenario, release the tradie
    //         $this->availability->releaseTradie($call->tradie_id);
    //     }

    //     if ($call) {
    //         $call->update([
    //             'status'   => 'completed',
    //             'ended_at' => now(),
    //             'duration_seconds' => $payload['duration_ms'] ? (int)($payload['duration_ms'] / 1000) : 0,
    //         ]);
    //     }

    //     return response()->json(['status' => 'ok']);
    // }
    private function onCallEnded(array $payload): JsonResponse
    {
        $retellCallId = $payload['call_id'];

        $call = Call::where('ai_session_id', $retellCallId)->first();

        if ($call && $call->tradie_id) {
            $this->availability->releaseTradie($call->tradie_id);
        }

        if ($call) {
            $call->update([
                'status'          => 'completed',
                'ended_at'        => now(),
                'duration_seconds'=> (int) ($payload['duration_ms'] / 1000),
            ]);
        }

        // Even if no call record exists yet, release tradie by agent_id
        if (! $call) {
            $tradie = \App\Domain\Tradie\Models\Tradie::where(
                'retell_agent_id', $payload['agent_id'] ?? ''
            )->first();

            if ($tradie) {
                $this->availability->releaseTradie($tradie->id);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Fired after Retell completes post-call analysis (with custom_analysis_data populated).
     * This is where we create the job from AI-extracted details.
     */
    // private function onCallAnalyzed(array $payload): JsonResponse
    // {
    //     $retellCallId = $payload['call_id'];

    //     $call = Call::where('ai_session_id', $retellCallId)->first();
    //     if (! $call) {
    //         Log::warning('Retell onCallAnalyzed: no call found', ['retell_call_id' => $retellCallId]);
    //         return response()->json(['status' => 'not_found'], 404);
    //     }
    //     $tradie = Tradie::find($call->tradie_id);
    //     if(! $tradie) {
    //         Log::warning('Retell onCallAnalyzed: no tradie found', ['tradie_id' => $call->tradie_id]);
    //         return response()->json(['status' => 'not_found'], 404);
    //     }

    //     // Track usage — Retell gives duration in seconds, convert to minutes
    //     $durationMinutes = (int) ceil(($payload['call']['duration_ms'] ?? 0) / 60000);
    //     $tradie->incrementAIMinutes($durationMinutes);

    //     // Don't create duplicate jobs for the same call
    //     if ($call->job()->exists()) {
    //         return response()->json(['status' => 'already_processed']);
    //     }

    //     $details = $this->aiVoiceService->extractJobDetails($payload);

    //     $this->jobService->createFromAICall(
    //         $call->tradie_id,
    //         $call->id,
    //         $details,
    //     );

    //     return response()->json(['status' => 'job_created']);
    // }
    private function onCallAnalyzed(array $payload): JsonResponse
    {
        $retellCallId = $payload['call_id'];

        $call = Call::where('ai_session_id', $retellCallId)->first();

        // With SIP trunking, the call record may not exist yet
        // Create it from the Retell payload
        if (! $call) {
            $metadata   = $payload['metadata'] ?? [];
            $tradieId   = $metadata['tradie_id'] ?? null;
            $callSid    = $metadata['twilio_call_sid'] ?? null;

            // Find tradie — try metadata first, fall back to finding by agent
            $tradie = $tradieId
                ? \App\Domain\Tradie\Models\Tradie::find($tradieId)
                : \App\Domain\Tradie\Models\Tradie::where('retell_agent_id', $payload['agent_id'] ?? '')->first();

            if (! $tradie) {
                Log::warning('Retell onCallAnalyzed: no tradie found', [
                    'retell_call_id' => $retellCallId,
                    'agent_id'       => $payload['agent_id'] ?? null,
                ]);
                return response()->json(['status' => 'tradie_not_found'], 404);
            }

            // Create the call record retroactively
            $call = Call::create([
                'tradie_id'       => $tradie->id,
                'twilio_call_sid' => $callSid ?? 'sip_' . $retellCallId,
                'caller_number'   => $payload['from_number'] ?? 'unknown',
                'called_number'   => $payload['to_number'] ?? $tradie->business_number,
                'status'          => 'completed',
                'direction'       => 'inbound',
                'ai_session_id'   => $retellCallId,
                'started_at'      => now(),
                'ended_at'        => now(),
                'duration_seconds'=> (int) ceil(($payload['duration_ms'] ?? 0) / 1000),
            ]);

            Log::info('Retell onCallAnalyzed: created call record retroactively', [
                'call_id'        => $call->id,
                'retell_call_id' => $retellCallId,
            ]);
        }

        // Track AI minutes usage
        $tradie = \App\Domain\Tradie\Models\Tradie::find($call->tradie_id);
        if ($tradie) {
            $durationMinutes = (int) ceil(($payload['duration_ms'] ?? 0) / 60000);
            $tradie->incrementAIMinutes($durationMinutes);
        }

        // Don't create duplicate jobs
        if ($call->job()->exists()) {
            return response()->json(['status' => 'already_processed']);
        }

        $details = $this->aiVoiceService->extractJobDetails($payload);

        $this->jobService->createFromAICall(
            $call->tradie_id,
            $call->id,
            $details,
        );

        return response()->json(['status' => 'job_created']);
    }
}
