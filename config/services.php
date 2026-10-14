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

    /*
    | Private vehicle API of BPAD NTT (NJKB API NTT), reached server to server
    | with PINTU's own Cloudflare Access Service Token (D-041). The secret
    | never reaches the browser.
    */
    'njkb' => [
        'base_url' => env('NJKB_API_BASE_URL', 'https://api.uptdpenda-kupang.web.id'),
        'client_id' => env('NJKB_ACCESS_CLIENT_ID'),
        'client_secret' => env('NJKB_ACCESS_CLIENT_SECRET'),
        'timeout' => (int) env('NJKB_API_TIMEOUT_SECONDS', 8),
        // BPAD's service hours in WITA ("HH:MM", D-042); no opening time means it is not known yet
        'opens_at' => env('NJKB_SERVICE_OPENS_AT'),
        'closes_at' => env('NJKB_SERVICE_CLOSES_AT', '21:00'),
    ],

];
