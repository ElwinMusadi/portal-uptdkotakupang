<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    /**
     * Prove GET /api/health returns exact JSON {"status":"ok"}
     * without requiring active session, authentication, or database connection.
     */
    public function test_health_check_returns_exact_ok_json_without_session_auth_or_db(): void
    {
        // Deliberately disable database connection to ensure endpoint does not query or rely on DB
        config(['database.default' => null]);

        $response = $this->get('/api/health');

        $response->assertStatus(200);
        $response->assertExactJson([
            'status' => 'ok',
        ]);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Prove API routes render JSON responses for errors without Accept: application/json header.
     */
    public function test_api_route_renders_json_on_not_found_without_accept_header(): void
    {
        $response = $this->get('/api/non-existent-endpoint');

        $response->assertStatus(404);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Prove root /api path error renders JSON without Accept header.
     */
    public function test_api_root_not_found_renders_json_without_accept_header(): void
    {
        $response = $this->get('/api');

        $response->assertStatus(404);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Prove API error does not leak debug information or stack trace when APP_DEBUG is false.
     */
    public function test_api_error_does_not_leak_debug_information_when_debug_disabled(): void
    {
        config(['app.debug' => false]);

        $response = $this->get('/api/non-existent-endpoint');

        $response->assertStatus(404);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJsonMissingPath('trace');
        $response->assertJsonMissingPath('file');
        $response->assertJsonMissingPath('line');
        $response->assertJsonMissingPath('exception');
    }
}
