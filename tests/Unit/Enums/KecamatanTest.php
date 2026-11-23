<?php

namespace Tests\Unit\Enums;

use App\Enums\Kecamatan;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

/**
 * The kelurahan a name written anyhow in an STS file means (D-099).
 */
class KecamatanTest extends TestCase
{
    #[TestWith(['Oebobo', 'OEBOBO'])]
    #[TestWith(['  kelapa  lima ', 'KELAPA LIMA'])]
    #[TestWith(['KELAPA LIMA', 'KELAPA LIMA'])]
    #[TestWith(['Kel. Oesapa', 'OESAPA'])]
    #[TestWith(['KEL.OESAPA BARAT', 'OESAPA BARAT'])]
    #[TestWith(['Kel Naimata', 'NAIMATA'])]
    #[TestWith(['KELURAHAN SIKUMANA', 'SIKUMANA'])]
    #[TestWith(['Bakunase 2', 'BAKUNASE II'])]
    #[TestWith(['BAKUNASE II', 'BAKUNASE II'])]
    #[TestWith(['Bakunase', 'BAKUNASE'])]
    #[TestWith(['Naikoten 1', 'NAIKOTEN I'])]
    #[TestWith(['NAIKOTEN1', 'NAIKOTEN I'])]
    #[TestWith(['Naikoten Dua', 'NAIKOTEN II'])]
    #[TestWith(['Manulai 2', 'MANULAI II'])]
    #[TestWith(['Lai Lai Besi Kopan', 'LAI-LAI BESI KOPAN'])]
    #[TestWith(['Tuak-Daun-Merah', 'TUAK DAUN MERAH'])]
    #[TestWith(['Nunbaun Delha', 'NUNBAUN DELHA'])]
    public function test_finds_the_official_kelurahan_a_name_means(string $name, string $kelurahan): void
    {
        $this->assertSame($kelurahan, Kecamatan::officialKelurahan($name));
    }

    #[TestWith(['Oesao'])]
    #[TestWith(['-'])]
    #[TestWith([''])]
    #[TestWith(['Kel.'])]
    #[TestWith(['Bakunase 3'])]
    #[TestWith(['APA LIMA'])]
    public function test_finds_none_for_a_name_outside_the_list(string $name): void
    {
        $this->assertNull(Kecamatan::officialKelurahan($name));
    }
}
