<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class OverviewPageController extends Controller
{
    /**
     * The Ikhtisar page at /laporan-penerimaan (D-099, D-100). It holds no
     * figures: they come from GET /api/ikhtisar, which answers only while an
     * administrator has the page open. The page is kept out of caches,
     * search engines and the Referer header.
     */
    public function __invoke(): Response
    {
        return response()->view('ikhtisar')
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
