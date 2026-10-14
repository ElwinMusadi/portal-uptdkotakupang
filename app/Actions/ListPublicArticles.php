<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\Article;
use Illuminate\Database\QueryException;

class ListPublicArticles
{
    /**
     * List the published articles, newest first, for the portal's Informasi
     * page, its "Baca juga" list and the homepage announcement: everything
     * but the body, without IDs or workflow details other than the time of
     * publication. A source that cannot be read fails the request instead
     * of showing no news.
     *
     * @return list<array{judul: string, slug: string, kategori: string, ringkasan: string, sampul: ?string, keteranganSampul: string, tanggal: string, sorotan: bool, pengumumanBeranda: bool, menitBaca: int, terbitPada: ?string}>
     *
     * @throws PublicContentUnavailableException
     */
    public function handle(): array
    {
        try {
            $articles = Article::query()->published()->orderByDesc('tanggal')->orderByDesc('terbit_pada')->orderByDesc('id')->get();
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The articles could not be read.', previous: $exception);
        }

        return $articles->map(fn (Article $article): array => [
            'judul' => $article->judul,
            'slug' => $article->slug,
            'kategori' => $article->kategori,
            'ringkasan' => $article->ringkasan,
            'sampul' => $article->sampul,
            'keteranganSampul' => $article->keterangan_sampul,
            'tanggal' => $article->tanggal->toDateString(),
            'sorotan' => $article->sorotan,
            'pengumumanBeranda' => $article->pengumuman_beranda,
            'menitBaca' => $article->readingMinutes(),
            'terbitPada' => $article->terbit_pada?->toIso8601String(),
        ])->values()->all();
    }
}
