<?php

namespace Tests\Unit\Config;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EnvironmentConfigTest extends TestCase
{
    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function corsOriginLists(): array
    {
        return [
            'empty' => ['', []],
            'exact origins with spaces and a port' => [
                ' https://pintu.example , http://127.0.0.1:8080 ',
                ['https://pintu.example', 'http://127.0.0.1:8080'],
            ],
            'wildcard' => ['*', []],
            'subdomain wildcard' => ['https://*.pintu.example', []],
            'trailing slash' => ['https://pintu.example/', []],
            'path' => ['https://pintu.example/api', []],
            'missing scheme' => ['pintu.example', []],
            'wildcard next to an exact origin' => ['*,https://pintu.example', ['https://pintu.example']],
        ];
    }

    /**
     * @param  list<string>  $expected
     */
    #[DataProvider('corsOriginLists')]
    public function test_cors_keeps_only_exact_origins(string $value, array $expected): void
    {
        $config = $this->configWithEnvironment('cors', 'CORS_ALLOWED_ORIGINS', $value);

        $this->assertSame($expected, $config['allowed_origins']);
    }

    /**
     * @return array<string, array{string, string, string, int}>
     */
    public static function limitValues(): array
    {
        return [
            'collection bound' => ['PINTU_PUBLIC_FAQ_MAX_ITEMS', 'public_faq.max_items', '25', 25],
            'empty collection bound' => ['PINTU_PUBLIC_FAQ_MAX_ITEMS', 'public_faq.max_items', '', 100],
            'zero collection bound' => ['PINTU_PUBLIC_FAQ_MAX_ITEMS', 'public_faq.max_items', '0', 100],
            'negative collection bound' => ['PINTU_PUBLIC_FAQ_MAX_ITEMS', 'public_faq.max_items', '-5', 100],
            'fractional collection bound' => ['PINTU_PUBLIC_FAQ_MAX_ITEMS', 'public_faq.max_items', '2.5', 100],
            'rate limit' => ['PINTU_PUBLIC_API_REQUESTS_PER_MINUTE', 'public_api.requests_per_minute', '120', 120],
            'non-numeric rate limit' => ['PINTU_PUBLIC_API_REQUESTS_PER_MINUTE', 'public_api.requests_per_minute', 'banyak', 60],
        ];
    }

    #[DataProvider('limitValues')]
    public function test_limits_fall_back_to_their_default_unless_a_whole_number_of_at_least_one(
        string $variable,
        string $key,
        string $value,
        int $expected,
    ): void {
        $config = $this->configWithEnvironment('pintu', $variable, $value);

        $this->assertSame($expected, data_get($config, $key));
    }

    /**
     * Evaluate a config file while one environment variable has the given value.
     *
     * @return array<string, mixed>
     */
    private function configWithEnvironment(string $file, string $variable, string $value): array
    {
        $previous = $_SERVER[$variable] ?? null;
        $_SERVER[$variable] = $value;

        try {
            return require config_path("{$file}.php");
        } finally {
            if ($previous === null) {
                unset($_SERVER[$variable]);
            } else {
                $_SERVER[$variable] = $previous;
            }
        }
    }
}
