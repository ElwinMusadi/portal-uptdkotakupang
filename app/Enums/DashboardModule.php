<?php

namespace App\Enums;

/**
 * Dashboard modules an administrator assigns to employees (D-010).
 * Administrators manage every module. Each value is the name of the
 * module's main collection or setting in the dashboard data model, so a
 * content endpoint can find the module it belongs to.
 */
enum DashboardModule: string
{
    case Pesan = 'pesan';
    case Statistik = 'statistik';
    case Berita = 'berita';
    case Dokumentasi = 'dokumentasi';
    case Dokumen = 'dokumen';
    case Faq = 'faq';
    case Layanan = 'layanan';
    case Jam = 'jam';
    case Keliling = 'keliling';
    case Papan = 'papan';
    case Tarif = 'tarif';
    case Profil = 'profil';
    case VisiMisi = 'visiMisi';
    case Unit = 'unit';
    case Kontak = 'kontak';
    case Beranda = 'beranda';
    case Halaman = 'halaman';
    case Pemutakhiran = 'pemutakhiran';

    /**
     * The module's name as the dashboard menu shows it.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pesan => 'Pesan & Pengaduan',
            self::Statistik => 'Statistik Layanan',
            self::Berita => 'Berita & Pengumuman',
            self::Dokumentasi => 'Dokumentasi Kegiatan',
            self::Dokumen => 'Regulasi & Unduhan',
            self::Faq => 'Tanya Jawab',
            self::Layanan => 'Persyaratan Layanan',
            self::Jam => 'Jam Pelayanan',
            self::Keliling => 'Samsat Keliling',
            self::Papan => 'Papan Layanan',
            self::Tarif => 'Tarif & Simulasi',
            self::Profil => 'Tentang UPTD',
            self::VisiMisi => 'Visi & Misi',
            self::Unit => 'Struktur Organisasi',
            self::Kontak => 'Kontak & Kanal',
            self::Beranda => 'Beranda',
            self::Halaman => 'Halaman Statis',
            self::Pemutakhiran => 'Jadwal Pemutakhiran',
        };
    }

    /**
     * The given module values without duplicates, in the order of the cases.
     *
     * @param  array<int, string>  $values
     * @return list<string>
     */
    public static function ordered(array $values): array
    {
        return array_values(array_map(
            fn (self $module): string => $module->value,
            array_filter(self::cases(), fn (self $module): bool => in_array($module->value, $values, true)),
        ));
    }
}
