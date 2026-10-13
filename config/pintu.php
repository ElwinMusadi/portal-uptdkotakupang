<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public API
    |--------------------------------------------------------------------------
    |
    | Anonymous, read-only endpoints under /api/public that the public portal
    | reads. Requests are throttled per client IP address; behind a proxy or
    | load balancer the trusted proxies must be configured so that the real
    | client address is used.
    |
    | Limits must be whole numbers of at least 1. Anything else, including an
    | empty value, falls back to the default.
    |
    */

    'public_api' => [
        'requests_per_minute' => filter_var(env('PINTU_PUBLIC_API_REQUESTS_PER_MINUTE'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Public FAQ
    |--------------------------------------------------------------------------
    |
    | Upper bound on the complete public FAQ collection. When more entries are
    | eligible, GET /api/public/faq fails with 503 instead of returning a
    | partial list. Entries withheld for invalid text still count.
    |
    */

    'public_faq' => [
        'max_items' => filter_var(env('PINTU_PUBLIC_FAQ_MAX_ITEMS'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Database
    |--------------------------------------------------------------------------
    |
    | `composer test` runs on in-memory SQLite. `composer test:mysql` sets
    | PINTU_TEST_MYSQL=1 and runs the same suite on the local MySQL database
    | named by DB_TEST_DATABASE. The tests reset that database, so it must
    | never hold data you want to keep.
    |
    */

    'testing' => [
        'mysql' => filter_var(env('PINTU_TEST_MYSQL', false), FILTER_VALIDATE_BOOL),
        'mysql_database' => env('DB_TEST_DATABASE'),
    ],

];
