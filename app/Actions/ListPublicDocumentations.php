<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\Documentation;
use Illuminate\Database\QueryException;

class ListPublicDocumentations
{
    /**
     * List the published activities and their photos, newest first, for
     * the portal's Dokumentasi Kegiatan gallery, without IDs or workflow
     * details other than the time of publication. A source that cannot be
     * read fails the request instead of showing no photos.
     *
     * @return list<array{judul: string, kategori: string, lokasi: string, tanggal: string, deskripsi: string, foto: list<array{src: string, keterangan: string}>, terbitPada: ?string}>
     *
     * @throws PublicContentUnavailableException
     */
    public function handle(): array
    {
        try {
            $activities = Documentation::query()->published()->orderByDesc('tanggal')->orderByDesc('terbit_pada')->orderByDesc('id')->get();
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The activities could not be read.', previous: $exception);
        }

        return $activities->map(fn (Documentation $documentation): array => [
            'judul' => $documentation->judul,
            'kategori' => $documentation->kategori,
            'lokasi' => $documentation->lokasi,
            'tanggal' => $documentation->tanggal->toDateString(),
            'deskripsi' => $documentation->deskripsi,
            'foto' => array_map(fn (array $photo): array => [
                'src' => $photo['src'],
                'keterangan' => $photo['keterangan'],
            ], $documentation->foto),
            'terbitPada' => $documentation->terbit_pada?->toIso8601String(),
        ])->values()->all();
    }
}
