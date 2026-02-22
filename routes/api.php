<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TwilioCallController;
use App\Http\Controllers\TwilioRecordingController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/twilio/incoming-call', [TwilioCallController::class, 'handle']);
Route::post('/twilio/recording', [TwilioRecordingController::class, 'recordingCallback'])->name('twilio.recording');


// Route::post('/twilio/fallback', function () {
//     $response = new \Twilio\TwiML\VoiceResponse();

//     $response->say(
//         'Sorry, the tradie is unavailable. I will take your job details.'
//     );

//     $gather = $response->gather([
//         'input' => 'speech',
//         'timeout' => 6,
//         'action' => route('twilio.recording'),
//         'speechTimeout' => 'auto',
//     ]);

//     $gather->say('Please tell me your name, service needed, and location.');

//     $response->record([
//         'recordingStatusCallback' => route('twilio.recording'),
//         'playBeep' => true,
//     ]);

//     return response($response)->header('Content-Type', 'text/xml');
// })->name('twilio.fallback');
Route::post('/twilio/fallback', function (Request $request) {

    $response = new \Twilio\TwiML\VoiceResponse();

    // Only trigger fallback if call was NOT answered
    if ($request->DialCallStatus !== 'completed') {

        $response->say(
            'Sorry, the tradie is unavailable. Please leave your name, service needed, and location after the beep.'
        );

        $response->record([
            'maxLength' => 35,
            'timeout' => 5, // stop after 5 sec silence
            'playBeep' => true,
            'recordingStatusCallback' => route('twilio.recording'),
        ]);

        $response->say('Thank you. We have received your job details.');
        $response->hangup();
    }

    return response($response, 200)->header('Content-Type', 'text/xml');
})->name('twilio.fallback');


