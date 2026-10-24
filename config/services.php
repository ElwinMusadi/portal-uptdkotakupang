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
    | Private vehicle API (api-potensi-ntt, Data Potensi BPAD NTT), reached
    | server to server with PINTU's own API key (D-041, D-076, D-078). The key
    | never reaches the browser.
    */
    'njkb' => [
        'base_url' => env('NJKB_API_BASE_URL', 'https://api.uptdpenda-kupang.web.id'),
        // PINTU's Data Potensi API key "ptn_…", covering every UPT in NTT (D-076, D-077);
        // the only credential since Cloudflare Access left the API host (D-078)
        'api_key' => env('NJKB_API_KEY'),
        'timeout' => (int) env('NJKB_API_TIMEOUT_SECONDS', 8),
        // BPAD's service hours in WITA ("HH:MM", D-042); no opening time means it is not known yet
        'opens_at' => env('NJKB_SERVICE_OPENS_AT'),
        'closes_at' => env('NJKB_SERVICE_CLOSES_AT', '21:00'),
    ],

    /*
    | Read-only employee API of SIKAP, the staffing system of UPTD, reached
    | server to server with PINTU's own API key (D-075). The key never
    | reaches the browser; without an https:// address or a key the employee
    | picker of Akun Pegawai reports that it is not configured.
    */
    'sikap' => [
        'base_url' => env('SIKAP_API_BASE_URL'),
        'key' => env('SIKAP_API_KEY'),
        'timeout' => (int) env('SIKAP_API_TIMEOUT_SECONDS', 8),
    ],

];
