<?php

namespace App\Models;

use App\Models\Concerns\HasPublicationWorkflow;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A document on the portal's Regulasi & Unduhan page (D-030): a regulation,
 * service standard, form or report, as a file uploaded to PINTU ("berkas",
 * described by its upload) or a link to an official site such as JDIH
 * ("tautan"). It goes through the publication workflow; only published
 * documents reach the portal.
 *
 * @property ?array{nama: string, ukuran: int, tipe: string, url: string} $berkas
 */
#[Fillable(['judul', 'keterangan', 'kategori', 'format', 'nomor', 'tahun', 'berkas', 'tautan', 'penyedia', 'pemeriksa'])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, HasPublicationWorkflow, HasUlids;

    /**
     * Categories in the order the portal lists them, as its filter shows them.
     *
     * @var list<string>
     */
    public const array CATEGORIES = ['regulasi', 'standar', 'formulir', 'laporan'];

    /**
     * Formats the portal labels a document with. "Tautan" is a link without
     * a file.
     *
     * @var list<string>
     */
    public const array FORMATS = ['PDF', 'DOCX', 'XLSX', 'JPG', 'Tautan'];

    /**
     * The format of an uploaded file by its detected type; images are "JPG".
     *
     * @var array<string, string>
     */
    public const array FILE_FORMATS = [
        'application/pdf' => 'PDF',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'XLSX',
        'image/jpeg' => 'JPG',
        'image/png' => 'JPG',
        'image/webp' => 'JPG',
    ];

    /**
     * A new document's optional texts are empty, as in the table.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'keterangan' => '',
        'nomor' => '',
        'tahun' => '',
        'tautan' => '',
        'pemeriksa' => '',
        'catatan_revisi' => '',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'berkas' => 'array',
            'urutan' => 'integer',
        ];
    }

    /**
     * Whether the portal can offer the document: it has a file or a link.
     */
    public function isAvailable(): bool
    {
        return $this->berkas !== null || $this->tautan !== '';
    }
}
