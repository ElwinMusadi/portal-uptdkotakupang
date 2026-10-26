<?php

namespace App\Support;

use App\Models\Article;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * Preview links of Berita & Pengumuman (D-086): the portal's own article page
 * showing an article that is not published yet. A link is signed with
 * APP_KEY and opens for anyone who has it for HOURS, so a writer can send it
 * to the reviewer without an account (owner). It always shows the article as
 * last saved. The signature covers the path only, so the link survives the
 * scheme and host the proxies report.
 */
final class ArticlePreview
{
    public const int HOURS = 24;

    /**
     * @return array{tautan: string, berlaku: string}
     */
    public static function link(Article $article): array
    {
        $expires = Carbon::now()->addHours(self::HOURS);

        return [
            'tautan' => url(URL::temporarySignedRoute('portal.berita.pratinjau', $expires, ['article' => $article->getKey()], absolute: false)),
            'berlaku' => $expires->toIso8601String(),
        ];
    }
}
