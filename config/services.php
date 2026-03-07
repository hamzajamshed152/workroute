<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'twilio' => [
        'sid'                    => env('TWILIO_ACCOUNT_SID'),
        'token'                  => env('TWILIO_AUTH_TOKEN'),
        'skip_signature_check'   => env('TWILIO_SKIP_SIGNATURE_CHECK', false), // true only in local/testing
        'sip_username'           => env('TWILIO_SIP_USERNAME'),
        'sip_password'           => env('TWILIO_SIP_PASSWORD'),
    ],

    'retell' => [
        'api_key'        => env('RETELL_API_KEY'),
        'sip_host'       => env('RETELL_SIP_URI', 'sip.retellai.com'),
        'default_voice'  => env('RETELL_DEFAULT_VOICE', '11labs-Adrian'),
        'skip_signature_check'   => env('RETELL_SKIP_SIGNATURE_CHECK', false), // true only in local/testing
    ],

];
