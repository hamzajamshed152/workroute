<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ProcessRecordingJob;
use Illuminate\Support\Facades\Log;

class TwilioRecordingController extends Controller
{
    public function recordingCallback(Request $request)
    {
        ProcessRecordingJob::dispatch(
            $request->RecordingUrl,
            $request->From
        );

        Log::info('Recording URL', [$request->RecordingUrl]);

        return response('OK');
    }
}
