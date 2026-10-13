<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Thrown when a public collection cannot be served completely and correctly,
 * for example because its source is unreachable or more entries are eligible
 * than the configured bound. Visitors get a generic 503 instead of a partial
 * or empty list; the reason is only written to the application log.
 */
class PublicContentUnavailableException extends RuntimeException
{
    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'Konten sedang tidak dapat ditampilkan. Silakan coba lagi nanti.',
        ], 503);
    }
}
