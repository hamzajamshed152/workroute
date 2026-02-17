<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;

class TwilioCallController extends Controller
{
    public function handle(Request $request)
    {
        $response = new VoiceResponse();

        $tradiePhone = '+923324701243';

        $dial = $response->dial('', [
            'timeout' => 15,
            'action' => route('twilio.fallback'),
        ]);

        $dial->number($tradiePhone);

        return response($response, 200)
            ->header('Content-Type', 'text/xml');
    }
}
