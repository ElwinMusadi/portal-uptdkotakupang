<?php

namespace App\Http\Controllers;

use App\Actions\ShowPublicAnnouncementBar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicAnnouncementBarController extends Controller
{
    /**
     * Show the announcement bar for the portal's Informasi page (D-082) and
     * Beranda (D-083). Query parameters are rejected rather than silently
     * ignored.
     */
    public function __invoke(Request $request, ShowPublicAnnouncementBar $showAnnouncementBar): JsonResponse
    {
        if ($request->query() !== []) {
            throw ValidationException::withMessages([
                'query' => 'Parameter kueri tidak didukung.',
            ]);
        }

        return response()->json($showAnnouncementBar->handle());
    }
}
