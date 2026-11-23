<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The Ikhtisar page (D-099) holds no figures; it is kept out of caches,
 * search engines and the Referer header, and starts no session.
 */
class OverviewPageTest extends TestCase
{
    public function test_serves_the_page_without_figures_cookies_caching_or_indexing(): void
    {
        $response = $this->withoutVite()->get('/ikhtisar');

        $response->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertSee('<meta name="robots" content="noindex, nofollow" />', false)
            ->assertSee('<div id="root"></div>', false)
            ->assertCookieMissing(config('session.cookie'))
            ->assertCookieMissing('XSRF-TOKEN');
    }
}
