<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\Article;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class ShowPublicArticle
{
    /**
     * Show one published article for the portal's /berita/{slug} page, with
     * its body, photo credit, reviewer and times, but without its ID or
     * workflow history. Articles that are not published are not found. A
     * source that cannot be read fails the request.
     *
     * @return array{judul: string, slug: string, kategori: string, ringkasan: string, isi: string, sampul: ?string, keteranganSampul: string, kreditFoto: string, tanggal: string, pemeriksa: string, menitBaca: int, terbitPada: ?string, diperbarui: ?string}
     *
     * @throws ModelNotFoundException<Article>
     * @throws PublicContentUnavailableException
     */
    public function handle(string $slug): array
    {
        try {
            $article = Article::query()->published()->where('slug', $slug)->firstOrFail();
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The article could not be read.', previous: $exception);
        }

        return [
            'judul' => $article->judul,
            'slug' => $article->slug,
            'kategori' => $article->kategori,
            'ringkasan' => $article->ringkasan,
            'isi' => $article->isi,
            'sampul' => $article->sampul,
            'keteranganSampul' => $article->keterangan_sampul,
            'kreditFoto' => $article->kredit_foto,
            'tanggal' => $article->tanggal->toDateString(),
            'pemeriksa' => $article->pemeriksa,
            'menitBaca' => $article->readingMinutes(),
            'terbitPada' => $article->terbit_pada?->toIso8601String(),
            'diperbarui' => $article->updated_at?->toIso8601String(),
        ];
    }
}
