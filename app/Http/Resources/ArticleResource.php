<?php

namespace App\Http\Resources;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An article as the dashboard edits it, in the shape of the dashboard's
 * Berita model. The public portal uses PublicArticleController instead.
 *
 * @mixin Article
 */
class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array. Articles are listed by date,
     * so their order is always 0.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'judul' => $this->judul,
            'slug' => $this->slug,
            'kategori' => $this->kategori,
            'ringkasan' => $this->ringkasan,
            'isi' => $this->isi,
            'sampul' => $this->sampul,
            'keteranganSampul' => $this->keterangan_sampul,
            'kreditFoto' => $this->kredit_foto,
            'tanggal' => $this->tanggal->toDateString(),
            'sorotan' => $this->sorotan,
            'pengumumanBeranda' => $this->pengumuman_beranda,
            'status' => $this->status->value,
            'penyedia' => $this->penyedia,
            'pemeriksa' => $this->pemeriksa,
            'catatanRevisi' => $this->catatan_revisi,
            'terbitPada' => $this->terbit_pada?->toIso8601String(),
            'riwayat' => $this->riwayat,
            'urutan' => 0,
            'dibuat' => $this->created_at?->toIso8601String(),
            'diperbarui' => $this->updated_at?->toIso8601String(),
        ];
    }
}
