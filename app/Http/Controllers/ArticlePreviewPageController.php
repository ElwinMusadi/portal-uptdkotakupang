<?php

namespace App\Http\Controllers;

use App\Actions\ShowPublicArticle;
use App\Enums\WorkflowStatus;
use App\Http\PortalPage;
use App\Models\Article;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * The portal's article page for a preview link (D-086): the article as last
 * saved, whatever its status, written into the page for main.js, under a
 * band saying it is a preview. Search engines are kept out, the page is not
 * cached, and no Referer carries the link to other sites. A published
 * article sends the visitor to its own address.
 */
class ArticlePreviewPageController extends Controller
{
    public function __invoke(Request $request, Article $article): Response|RedirectResponse
    {
        if (! $request->hasValidRelativeSignature()) {
            return $this->page(
                'Tautan pratinjau tidak berlaku',
                ['galat' => 'Tautan ini sudah kedaluwarsa atau tidak lengkap. Minta tautan pratinjau baru kepada pengelola berita.'],
                403,
            );
        }

        if ($article->status === WorkflowStatus::Published) {
            return redirect()->route('portal.berita', $article->slug);
        }

        $until = Carbon::createFromTimestamp((int) $request->query('expires'), 'Asia/Makassar');

        return $this->page(
            "Pratinjau · {$article->status->label()}, belum terbit. Tautan berlaku sampai {$until->locale('id')->translatedFormat('j M Y, H.i')} WITA.",
            ShowPublicArticle::present($article),
        );
    }

    /**
     * The article page with the band, the data and the meta that keep a
     * preview private.
     *
     * @param  array<string, mixed>  $data
     */
    private function page(string $band, array $data, int $status = 200): Response
    {
        $json = json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $html = PortalPage::fromSiteRoot(view('portal.berita-detail')->render());
        $html = Str::replaceFirst('<head>', "<head>\n  <meta name=\"robots\" content=\"noindex, nofollow\">\n  <meta name=\"referrer\" content=\"no-referrer\">", $html);
        // The band is written here from the status and the time, never from the article or the request
        $html = Str::replaceFirst('<main id="main">', '<main id="main">'
            .'<div class="pratinjau-pita" role="status"><div class="container"><p>'.$band.'</p></div></div>'
            .'<script type="application/json" data-berita-pratinjau>'.$json.'</script>', $html);

        return response($html, $status)
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
