<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\StaticPage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class ShowPublicStaticPage
{
    /**
     * Build a static page for the public portal, without its ID. Until the
     * page is first saved "diperbarui" is null and the portal keeps the text
     * in its markup. A source that cannot be read fails the request.
     *
     * @return array{judul: string, judulLanjutan: string, pengantar: string, isi: string, diperbarui: ?string}
     *
     * @throws ModelNotFoundException<StaticPage>
     * @throws PublicContentUnavailableException
     */
    public function handle(string $slug): array
    {
        try {
            $page = StaticPage::query()->where('slug', $slug)->firstOrFail();
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The static page could not be read.', previous: $exception);
        }

        return [
            'judul' => $page->judul,
            'judulLanjutan' => $page->judul_lanjutan,
            'pengantar' => $page->pengantar,
            'isi' => $page->isi,
            'diperbarui' => $page->updated_at?->toIso8601String(),
        ];
    }
}
