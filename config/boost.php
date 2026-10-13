<?php

declare(strict_types=1);

return [
    // Local stdio tooling only; do not activate Boost in hosted environments.
    'enabled' => env('APP_ENV', 'production') === 'local' && env('BOOST_ENABLED', true),

    // Preserve application HTML and avoid Boost's browser-log HTTP endpoint.
    'browser_logs_watcher' => false,
];
