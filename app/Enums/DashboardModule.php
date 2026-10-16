<?php

namespace App\Enums;

/**
 * Dashboard modules an administrator assigns to employees (D-010).
 * Administrators manage every module. Each value is the name of the
 * module's main collection or setting in the dashboard data model, so a
 * content endpoint can find the module it belongs to. A retired module stays
 * a case so earlier activity entries still read, but nobody manages it.
 */
enum DashboardModule: string
{
    case Pesan = 'pesan';
    case Statistik = 'statistik';
    /** Laporan Penerimaan: revenue imported from BPAD's STS files (D-047). */
    case Penerimaan = 'penerimaan';
    /** Pemetaan PKB: the vehicles due for tax and their compliance (D-052). */
    case Pemetaan = 'pemetaan';
    case Berita = 'berita';
    case Dokumentasi = 'dokumentasi';
    case Dokumen = 'dokumen';
    case Faq = 'faq';
    case Layanan = 'layanan';
    case Jam = 'jam';
    case Keliling = 'keliling';
    case Papan = 'papan';
    /** Antrean Online: queue numbers printed at the information desk (D-051). */
    case Antrean = 'antrean';
    /** Tarif & Simulasi, retired by D-041: the tax engine sets the rates. */
    case Tarif = 'tarif';
    case Profil = 'profil';
    case VisiMisi = 'visiMisi';
    case Unit = 'unit';
    case Kontak = 'kontak';
    case Beranda = 'beranda';
    case Halaman = 'halaman';
    case Pemutakhiran = 'pemutakhiran';
    /** Aplikasi & Situs: the apps and websites of BPAD and the UPTD (D-054). */
    case Aplikasi = 'aplikasi';

    /**
     * The module's name as the dashboard menu shows it.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pesan => 'Pesan & Pengaduan',
            self::Statistik => 'Statistik Layanan',
            self::Penerimaan => 'Laporan Penerimaan',
            self::Pemetaan => 'Pemetaan PKB',
            self::Berita => 'Berita & Pengumuman',
            self::Dokumentasi => 'Dokumentasi Kegiatan',
            self::Dokumen => 'Regulasi & Unduhan',
            self::Faq => 'Tanya Jawab',
            self::Layanan => 'Persyaratan Layanan',
            self::Jam => 'Jam Pelayanan',
            self::Keliling => 'Layanan Luar Kantor',
            self::Papan => 'Papan Layanan',
            self::Antrean => 'Antrean Online',
            self::Tarif => 'Tarif & Simulasi',
            self::Profil => 'Tentang UPTD',
            self::VisiMisi => 'Visi & Misi',
            self::Unit => 'Struktur Organisasi',
            self::Kontak => 'Kontak & Kanal',
            self::Beranda => 'Beranda',
            self::Halaman => 'Halaman Statis',
            self::Pemutakhiran => 'Jadwal Pemutakhiran',
            self::Aplikasi => 'Aplikasi & Situs',
        };
    }

    /**
     * Whether the module is no longer part of the dashboard.
     */
    public function isRetired(): bool
    {
        return $this === self::Tarif;
    }

    /**
     * The modules that can be assigned and managed, in the order of the cases.
     *
     * @return list<self>
     */
    public static function assignable(): array
    {
        return array_values(array_filter(self::cases(), fn (self $module): bool => ! $module->isRetired()));
    }

    /**
     * The given module values without duplicates or retired modules, in the
     * order of the cases.
     *
     * @param  array<int, string>  $values
     * @return list<string>
     */
    public static function ordered(array $values): array
    {
        return array_values(array_map(
            fn (self $module): string => $module->value,
            array_filter(self::assignable(), fn (self $module): bool => in_array($module->value, $values, true)),
        ));
    }
}
