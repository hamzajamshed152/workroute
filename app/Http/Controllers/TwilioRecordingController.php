<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ProcessRecordingJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Twilio\TwiML\VoiceResponse;

class TwilioRecordingController extends Controller
{
    // public function recordingCallback(Request $request)
    // {
    //     ProcessRecordingJob::dispatch(
    //         $request->RecordingUrl,
    //         $request->From
    //     );

    //     Log::info('Recording URL', [$request->RecordingUrl]);

    //     return response('OK');
    // }

    public function recordingCallback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'RecordingUrl'  => 'required|string',
            'From'  => 'required|string',
        ]);


        ProcessRecordingJob::dispatch(
            $request->RecordingUrl,
            $request->From
        );

        Log::info('Recording URL', [$request->RecordingUrl]);

        $response = new VoiceResponse();
        $response->say('Thank you. We have received your job details.');

        return response($response, 200)
            ->header('Content-Type', 'text/xml');
    }

}
