<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class ThemeStylesheetController extends Controller
{
    /**
     * Serve /tema-portal.css, the stylesheet every portal page, the 404,
     * sign-in, activation and TV display pages load after their own (D-057).
     * It will override the design tokens with the portal theme an
     * administrator saves; until then it holds only a comment, so the
     * built-in PINTU look applies.
     */
    public function portal(): Response
    {
        return $this->stylesheet('/* Tema portal PINTU: tampilan bawaan, belum diatur dari dashboard (D-057). */');
    }

    /**
     * Serve /tema-dashboard.css, which the dashboard and Absensi load after
     * their built stylesheet (D-057); it holds only a comment until a
     * dashboard theme is saved.
     */
    public function dashboard(): Response
    {
        return $this->stylesheet('/* Tema dashboard PINTU: tampilan bawaan, belum diatur dari dashboard (D-057). */');
    }

    /**
     * A stylesheet response; its route revalidates it on every load
     * (no-cache with an ETag), so a saved theme shows at once.
     */
    private function stylesheet(string $css): Response
    {
        return response($css."\n")->header('Content-Type', 'text/css; charset=UTF-8');
    }
}
