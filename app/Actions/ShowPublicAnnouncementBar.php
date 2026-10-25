<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\Article;
use App\Models\Setting;
use Illuminate\Database\QueryException;

class ShowPublicAnnouncementBar
{
    /**
     * The announcement bar for the portal's Informasi page (D-082) and, when
     * chosen, Beranda (D-083). Until it is first saved it is the bar in the
     * Informasi page's markup and "diperbarui" is null, so the portal keeps
     * the markup as it is. A bar taken from a Tulisan shows its title and
     * category and leads to it; while that Tulisan is not published the bar
     * does not show. A written bar is labelled Pengumuman. A source that
     * cannot be read fails the request.
     *
     * @return array{aktif: bool, beranda: bool, kategori: string, teks: string, tautanLabel: string, tautanUrl: string, diperbarui: ?string}
     *
     * @throws PublicContentUnavailableException
     */
    public function handle(): array
    {
        try {
            $setting = Setting::query()->find(Setting::ANNOUNCEMENT_BAR);
            $bar = Setting::withAnnouncementBarDefaults($setting?->value);
            $article = $bar['sumber'] === 'tulisan'
                ? Article::query()->published()->find($bar['tulisan'])
                : null;
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The announcement bar could not be read.', previous: $exception);
        }

        if ($bar['sumber'] === 'tulisan') {
            $bar['aktif'] = $bar['aktif'] && $article !== null;
            $bar['teks'] = $article?->judul ?? '';
            $bar['tautanUrl'] = $article === null ? '' : route('portal.berita', $article->slug, false);
        }

        return [
            'aktif' => $bar['aktif'],
            'beranda' => $bar['beranda'],
            'kategori' => $article?->kategori ?? 'pengumuman',
            'teks' => $bar['teks'],
            'tautanLabel' => $bar['tautanLabel'],
            'tautanUrl' => $bar['tautanUrl'],
            'diperbarui' => $setting?->updated_at?->toIso8601String(),
        ];
    }
}
