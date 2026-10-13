<?php

namespace Tests\Support;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

/**
 * CSRF verification that also runs under PHPUnit (Laravel skips it in unit
 * tests), so a test can prove that state-changing requests require a token.
 */
class EnforcedCsrfToken extends ValidateCsrfToken
{
    protected function runningUnitTests(): bool
    {
        return false;
    }
}
