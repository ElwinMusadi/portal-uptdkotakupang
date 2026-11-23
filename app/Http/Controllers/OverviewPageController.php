<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class OverviewPageController extends Controller
{
    /**
     * The Ikhtisar page (D-099). It holds no figures: they come from
     * GET /api/ikhtisar with the token of the link, which stays in the
     * address's fragment and so never reaches the server here. The page is
     * kept out of caches, search engines and the Referer header.
     */
    public function __invoke(): Response
    {
        return response()->view('ikhtisar')
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
