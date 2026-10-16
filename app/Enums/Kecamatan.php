<?php

namespace App\Enums;

/**
 * The six kecamatan of Kota Kupang and their 51 kelurahan (D-052), spelled
 * as BPAD's files write them. Pemetaan PKB places a vehicle by its
 * kelurahan through this list: the kecamatan column of the potential-tax file
 * names several kecamatan for the same kelurahan.
 */
enum Kecamatan: string
{
    case Alak = 'ALAK';
    case KelapaLima = 'KELAPA LIMA';
    case KotaLama = 'KOTA LAMA';
    case KotaRaja = 'KOTA RAJA';
    case Maulafa = 'MAULAFA';
    case Oebobo = 'OEBOBO';

    /**
     * The kecamatan's name as the dashboard and portal show it.
     */
    public function label(): string
    {
        return match ($this) {
            self::Alak => 'Alak',
            self::KelapaLima => 'Kelapa Lima',
            self::KotaLama => 'Kota Lama',
            self::KotaRaja => 'Kota Raja',
            self::Maulafa => 'Maulafa',
            self::Oebobo => 'Oebobo',
        };
    }

    /**
     * The kecamatan's kelurahan, as the files write them.
     *
     * @return list<string>
     */
    public function kelurahan(): array
    {
        return match ($this) {
            self::Alak => ['ALAK', 'BATUPLAT', 'FATUFETO', 'MANTASI', 'MANULAI II', 'MANUTAPEN', 'NAIONI', 'NAMOSAIN', 'NUNBAUN DELHA', 'NUNBAUN SABU', 'NUNHILA', 'PENKASE OELETA'],
            self::KelapaLima => ['KELAPA LIMA', 'LASIANA', 'OESAPA', 'OESAPA BARAT', 'OESAPA SELATAN'],
            self::KotaLama => ['AIRMATA', 'BONIPOI', 'FATUBESI', 'LAI-LAI BESI KOPAN', 'MERDEKA', 'NEFONAEK', 'OEBA', 'PASIR PANJANG', 'SOLOR', 'TODE KISAR'],
            self::KotaRaja => ['AIRNONA', 'BAKUNASE', 'BAKUNASE II', 'FONTEIN', 'KUANINO', 'NAIKOTEN I', 'NAIKOTEN II', 'NUNLEU'],
            self::Maulafa => ['BELLO', 'FATUKOA', 'KOLHUA', 'MAULAFA', 'NAIKOLAN', 'NAIMATA', 'OEPURA', 'PENFUI', 'SIKUMANA'],
            self::Oebobo => ['FATULULI', 'KAYU PUTIH', 'LILIBA', 'OEBOBO', 'OEBUFU', 'OETETE', 'TUAK DAUN MERAH'],
        };
    }

    /**
     * The kecamatan of a kelurahan written as in the files, or null for a
     * name outside Kota Kupang's list.
     */
    public static function ofKelurahan(string $kelurahan): ?self
    {
        foreach (self::cases() as $kecamatan) {
            if (in_array($kelurahan, $kecamatan->kelurahan(), true)) {
                return $kecamatan;
            }
        }

        return null;
    }
}
