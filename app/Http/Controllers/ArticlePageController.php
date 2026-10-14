<?php

namespace App\Http\Controllers;

use App\Http\PortalPage;
use Illuminate\Http\Response;

class ArticlePageController extends Controller
{
    /**
     * Serve the portal's article page at /berita/{slug}; main.js fills it
     * from GET /api/public/berita/{slug} (D-031). Its addresses are made
     * root-relative, since they would otherwise resolve under /berita/.
     */
    public function __invoke(): Response
    {
        return PortalPage::render('portal.berita-detail');
    }
}
