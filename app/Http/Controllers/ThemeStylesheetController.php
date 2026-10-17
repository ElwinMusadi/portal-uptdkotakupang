<?php

namespace App\Http\Controllers;

use App\Actions\BuildThemeStylesheet;
use App\Models\Setting;
use Illuminate\Http\Response;

class ThemeStylesheetController extends Controller
{
    /**
     * Serve /tema-portal.css, the stylesheet every portal page, the 404,
     * sign-in, activation and TV display pages load after their own (D-057):
     * the portal theme an administrator saved in Pengaturan › Tampilan, or
     * only a comment while it is the built-in PINTU look.
     */
    public function portal(BuildThemeStylesheet $buildStylesheet): Response
    {
        return $this->stylesheet($buildStylesheet->portal(Setting::theme()['portal']));
    }

    /**
     * Serve /tema-dashboard.css, which the dashboard and Absensi load after
     * their built stylesheet (D-057): the dashboard theme, or only a comment
     * while it is the built-in look.
     */
    public function dashboard(BuildThemeStylesheet $buildStylesheet): Response
    {
        return $this->stylesheet($buildStylesheet->dashboard(Setting::theme()['dashboard']));
    }

    /**
     * A stylesheet response; its route revalidates it on every load
     * (no-cache with an ETag), so a saved theme shows at once.
     */
    private function stylesheet(string $css): Response
    {
        return response($css)->header('Content-Type', 'text/css; charset=UTF-8');
    }
}
