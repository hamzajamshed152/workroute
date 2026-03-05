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
    private function onCallEnded(array $payload): JsonResponse
    {
        $retellCallId = $payload['call_id'];

        $call = Call::where('ai_session_id', $retellCallId)->first();

        if ($call && $call->tradie_id) {
            // If this was a no-answer scenario, release the tradie
            $this->availability->releaseTradie($call->tradie_id);
        }

        if ($call) {
            $call->update([
                'status'   => 'completed',
                'ended_at' => now(),
                'duration_seconds' => $payload['duration_ms'] ? (int)($payload['duration_ms'] / 1000) : 0,
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Fired after Retell completes post-call analysis (with custom_analysis_data populated).
     * This is where we create the job from AI-extracted details.
     */
    private function onCallAnalyzed(array $payload): JsonResponse
    {
        $retellCallId = $payload['call_id'];

        $call = Call::where('ai_session_id', $retellCallId)->first();
        if (! $call) {
            Log::warning('Retell onCallAnalyzed: no call found', ['retell_call_id' => $retellCallId]);
            return response()->json(['status' => 'not_found'], 404);
        }
        $tradie = Tradie::find($call->tradie_id);
        if(! $tradie) {
            Log::warning('Retell onCallAnalyzed: no tradie found', ['tradie_id' => $call->tradie_id]);
            return response()->json(['status' => 'not_found'], 404);
        }

        // Track usage — Retell gives duration in seconds, convert to minutes
        $durationMinutes = (int) ceil(($payload['call']['duration_ms'] ?? 0) / 60000);
        $tradie->incrementAIMinutes($durationMinutes);

        // Don't create duplicate jobs for the same call
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
