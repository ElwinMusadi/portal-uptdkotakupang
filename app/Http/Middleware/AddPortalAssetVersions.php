<?php

namespace App\Http\Middleware;

use App\Http\PortalPage;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response as IlluminateResponse;
use Symfony\Component\HttpFoundation\Response;

class AddPortalAssetVersions
{
    /**
     * Version the stylesheets and scripts of every portal page served as
     * HTML (D-093), so a deploy reaches visitors whose browser kept the old
     * files under the edge's one-year cache. The page files stay as written.
     * A response without a content type yet (the 404 page) becomes HTML.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $type = $response->headers->get('Content-Type') ?? 'text/html';

        if ($response instanceof IlluminateResponse && str_starts_with($type, 'text/html')) {
            $response->setContent(PortalPage::withAssetVersions((string) $response->getContent()));
        }

        return $response;
    }
}
