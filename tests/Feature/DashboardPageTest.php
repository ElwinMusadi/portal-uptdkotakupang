<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    #[TestWith(['/dashboard'])]
    #[TestWith(['/dashboard/'])]
    public function test_serves_the_dashboard_shell_that_hosts_the_react_app(string $uri): void
    {
        $this->withoutVite();

        $response = $this->get($uri);

        $response->assertOk()
            ->assertSee('<div id="root"></div>', false)
            ->assertSee('<meta name="robots" content="noindex, nofollow" />', false);
    }
}
