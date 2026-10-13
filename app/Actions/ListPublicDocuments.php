<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\Document;
use Illuminate\Database\QueryException;

class ListPublicDocuments
{
    /**
     * List the published documents for the portal's Regulasi & Unduhan
     * page, by category in the order of its filter and then in the order
     * they were added, without IDs or workflow details other than the time
     * of publication. A source that cannot be read fails the request
     * instead of showing no documents.
     *
     * @return list<array{judul: string, keterangan: string, kategori: string, format: string, nomor: string, tahun: string, berkas: ?array{nama: string, ukuran: int, tipe: string, url: string}, tautan: string, terbitPada: ?string}>
     *
     * @throws PublicContentUnavailableException
     */
    public function handle(): array
    {
        try {
            $documents = Document::query()->published()->orderBy('urutan')->orderBy('id')->get();
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The documents could not be read.', previous: $exception);
        }

        return $documents
            ->sortBy(fn (Document $document): int => (int) array_search($document->kategori, Document::CATEGORIES, true))
            ->map(fn (Document $document): array => [
                'judul' => $document->judul,
                'keterangan' => $document->keterangan,
                'kategori' => $document->kategori,
                'format' => $document->format,
                'nomor' => $document->nomor,
                'tahun' => $document->tahun,
                'berkas' => $document->berkas,
                'tautan' => $document->tautan,
                'terbitPada' => $document->terbit_pada?->toIso8601String(),
            ])->values()->all();
    }
}
