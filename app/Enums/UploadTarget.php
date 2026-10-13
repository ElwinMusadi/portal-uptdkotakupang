<?php

namespace App\Enums;

/**
 * Where an uploaded file is used (D-028). The target decides the bucket,
 * the accepted types and who may upload: managers of the module the target
 * belongs to, or every employee for field attendance, which is no module.
 */
enum UploadTarget: string
{
    case Berita = 'berita';
    case Dokumentasi = 'dokumentasi';
    case Dokumen = 'dokumen';
    case Unit = 'unit';
    case VisiMisi = 'visiMisi';
    case Profil = 'profil';
    case Absensi = 'absensi';

    /**
     * Images by detected type, with the extension they are stored under.
     * The server re-encodes them, so their metadata is never kept.
     *
     * @var array<string, string>
     */
    public const array IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Documents by detected type, with the extension they are stored under.
     * Legacy .doc and .xls files are not accepted.
     *
     * @var array<string, string>
     */
    public const array DOCUMENT_TYPES = [
        'application/pdf' => 'pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    ];

    /**
     * Largest image, in kilobytes.
     */
    public const int MAX_IMAGE_KILOBYTES = 5120;

    /**
     * Largest document, in kilobytes.
     */
    public const int MAX_DOCUMENT_KILOBYTES = 10240;

    /**
     * The module whose managers may upload for this target; null when every
     * employee may.
     */
    public function module(): ?DashboardModule
    {
        return $this === self::Absensi ? null : DashboardModule::from($this->value);
    }

    /**
     * Whether the file goes to the public bucket. Attendance photos hold
     * personal data and stay private.
     */
    public function isPublic(): bool
    {
        return $this !== self::Absensi;
    }

    /**
     * The accepted types, with the extension each is stored under.
     *
     * @return array<string, string>
     */
    public function types(): array
    {
        return match ($this) {
            self::Dokumen => [...self::DOCUMENT_TYPES, ...self::IMAGE_TYPES],
            self::VisiMisi => ['application/pdf' => 'pdf'],
            default => self::IMAGE_TYPES,
        };
    }

    /**
     * The accepted formats as the error message names them.
     */
    public function formats(): string
    {
        return match ($this) {
            self::Dokumen => 'PDF, Word (DOCX), Excel (XLSX), JPG, PNG, atau WebP',
            self::VisiMisi => 'PDF',
            default => 'JPG, PNG, atau WebP',
        };
    }
}
