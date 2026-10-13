<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Exact origins only (scheme://host[:port], comma-separated in the env).
    // Wildcards, paths and trailing slashes are dropped, never widened, and
    // an empty list means browsers on other origins cannot read responses.
    'allowed_origins' => array_values(array_filter(
        array_map(trim(...), explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))),
        fn (string $origin): bool => preg_match('#^https?://[^/\s*]+$#', $origin) === 1,
    )),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
