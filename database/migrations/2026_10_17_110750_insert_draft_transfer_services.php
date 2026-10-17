<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Two services the Layanan page's guide could not offer (D-059): change of
 * owner within Kota Kupang and transfer in from another region. Their
 * requirements are drafted from the transfer-out services and still have to
 * be checked against the counter's requirement board, so they start as drafts
 * (owner, D-061); once staff publish them, the guide offers them by their
 * situations.
 */
return new class extends Migration
{
    /**
     * Run the migrations. A service whose slug is already taken is left as it
     * is.
     */
    public function up(): void
    {
        $now = now();
        $urutan = (int) (DB::table('services')->max('urutan') ?? -1);

        $services = [
            [
                'nama' => 'Balik nama di dalam Kota Kupang',
                'slug' => 'balik-nama',
                'ringkasan' => 'Ganti pemilik kendaraan yang tetap terdaftar di Kota Kupang (BBN).',
                'label' => ['Meterai Rp10.000'],
                'persyaratan' => [
                    ['Fotokopi KTP pemilik baru (alamat di Kota Kupang)', '2 lembar'],
                    ['Fotokopi KTP pemilik kendaraan lama', '2 lembar'],
                    ['Fotokopi STNK dan BPKB (lampirkan yang asli)', '2 lembar'],
                    ['Kuitansi pembelian bertanda tangan di atas meterai Rp10.000', '1 lembar'],
                    ['Bawa kendaraan untuk cek fisik', '—'],
                    ['Map', '1 buah'],
                ],
                'situasi' => ['Membeli kendaraan bekas yang terdaftar di Kota Kupang', 'Mengganti nama pemilik di STNK dan BPKB'],
                'catatan' => 'Kendaraan diperoleh dari hibah atau warisan? Tanyakan berkas penggantinya di meja informasi.',
            ],
            [
                'nama' => 'Mutasi masuk dari luar daerah',
                'slug' => 'mutasi-masuk',
                'ringkasan' => 'Pindah daftar kendaraan dari daerah lain ke Kota Kupang.',
                'label' => ['Berkas mutasi dari Samsat asal'],
                'persyaratan' => [
                    ['Berkas mutasi dari Samsat daerah asal (tersegel)', '1 berkas'],
                    ['Fotokopi KTP pemilik (alamat di Kota Kupang)', '2 lembar'],
                    ['Fotokopi STNK dan BPKB (lampirkan yang asli)', '2 lembar'],
                    ['Kuitansi pembelian bertanda tangan di atas meterai Rp10.000 (bila ganti pemilik)', '1 lembar'],
                    ['Bawa kendaraan untuk cek fisik', '—'],
                    ['Map', '1 buah'],
                ],
                'situasi' => ['Pindah ke Kota Kupang dengan kendaraan dari daerah lain', 'Membeli kendaraan dari luar daerah untuk dipakai di Kota Kupang'],
                'catatan' => 'Berkas mutasi diurus lebih dulu di Samsat daerah asal kendaraan.',
            ],
        ];

        foreach ($services as $service) {
            if (DB::table('services')->where('slug', $service['slug'])->exists()) {
                continue;
            }

            DB::table('services')->insert([
                'id' => (string) Str::ulid(),
                'nama' => $service['nama'],
                'slug' => $service['slug'],
                'ringkasan' => $service['ringkasan'],
                'loket' => 'Loket 3',
                'jenis' => 'layanan',
                'cek_fisik' => true,
                'non_tunai' => false,
                'diwakilkan' => false,
                'label' => json_encode($service['label']),
                'persyaratan' => json_encode(array_map(
                    fn (array $requirement, int $index): array => ['id' => 'p'.($index + 1), 'dokumen' => $requirement[0], 'jumlah' => $requirement[1]],
                    $service['persyaratan'],
                    array_keys($service['persyaratan']),
                )),
                'situasi' => json_encode($service['situasi']),
                'catatan' => $service['catatan'],
                'unggulan' => false,
                'urutan' => ++$urutan,
                'status' => 'draf',
                'penyedia' => 'Seksi Verifikasi',
                'pemeriksa' => '',
                'catatan_revisi' => '',
                'terbit_pada' => null,
                'riwayat' => json_encode([['waktu' => $now->toIso8601String(), 'oleh' => 'PINTU', 'aksi' => 'menyiapkan draf untuk diperiksa dengan papan persyaratan loket', 'ke' => 'draf']]),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations: only drafts are removed, never a service staff
     * have since published.
     */
    public function down(): void
    {
        DB::table('services')->whereIn('slug', ['balik-nama', 'mutasi-masuk'])->where('status', 'draf')->delete();
    }
};
