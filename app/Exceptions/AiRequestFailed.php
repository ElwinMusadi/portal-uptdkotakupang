<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The AI API gave no usable answer (D-085). The message is for the dashboard
 * in Indonesian; the provider's own error text, if any, is kept apart for
 * the administrator's connection test.
 */
class AiRequestFailed extends RuntimeException
{
    /**
     * @param  'not_configured'|'timeout'|'network'|'unauthorized'|'not_found'|'rate_limited'|'http'|'invalid_response'  $reason
     */
    public function __construct(
        public readonly string $reason,
        string $message,
        public readonly ?int $httpStatus = null,
        public readonly ?string $providerMessage = null,
    ) {
        parent::__construct($message);
    }
}
