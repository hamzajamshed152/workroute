<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;

class TwilioCallController extends Controller
{
    public function handle(Request $request)
    {
        $response = new VoiceResponse();

        $tradiePhone = '+923060551335';

        // $dial = $response->dial('', [
        //     'timeout' => 5,
        //     'action' => route('twilio.fallback'),
        // ]);
        $dial = $response->dial('', [
            'timeout' => 5,
            'action' => route('twilio.fallback'),
            'method' => 'POST'
        ]);

        $dial->number($tradiePhone);

        return response($response, status: 200)
            ->header('Content-Type', 'text/xml');
    }
}
