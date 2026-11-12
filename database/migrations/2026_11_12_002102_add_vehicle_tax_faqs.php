<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Common questions about vehicle tax for the portal's Tanya Jawab (D-094),
 * reviewed by the owner: five new entries and a fuller answer to the example
 * question "Apa itu Opsen PKB?" the owner entered in the dashboard. They
 * complete that list, so nothing happens where the example question does not
 * exist (tests, fresh installs). The Opsen answer is only replaced while it
 * is still the example answer, and a question already in the list is not
 * added twice. The new entries are published for the public and the list is
 * ordered as reviewed, with the other entries after them in their order.
 */
return new class extends Migration
{
    private const string OPSEN = 'Apa itu Opsen PKB?';

    private const string OPSEN_EXAMPLE_ANSWER = 'Opsen adalah pungutan pajak untuk daerah kabupaten/kota.';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $opsen = DB::table('faqs')->where('pertanyaan', self::OPSEN)->orderBy('urutan')->orderBy('id')->first();

        if ($opsen === null) {
            return;
        }

        DB::transaction(function () use ($opsen): void {
            $now = now();

            if ($opsen->jawaban === self::OPSEN_EXAMPLE_ANSWER) {
                DB::table('faqs')->where('id', $opsen->id)->update(['jawaban' => self::answers()[self::OPSEN], 'updated_at' => $now]);
            }

            $order = [];
            foreach (self::answers() as $question => $answer) {
                if ($question === self::OPSEN) {
                    $order[] = $opsen->id;

                    continue;
                }

                $existing = DB::table('faqs')->where('pertanyaan', $question)->value('id');
                if ($existing === null) {
                    $existing = strtolower((string) Str::ulid());
                    DB::table('faqs')->insert([
                        'id' => $existing,
                        'pertanyaan' => $question,
                        'jawaban' => $answer,
                        'status' => 'published',
                        'publik' => true,
                        'tampil' => true,
                        'urutan' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
                $order[] = $existing;
            }

            $others = DB::table('faqs')->whereNotIn('id', $order)->orderBy('urutan')->orderBy('id')->pluck('id')->all();
            foreach ([...$order, ...$others] as $urutan => $id) {
                DB::table('faqs')->where('id', $id)->update(['urutan' => $urutan]);
            }
        });
    }

    /**
     * Reverse the migrations: remove the added entries that nobody changed
     * and put back the example Opsen answer while it is still this one.
     */
    public function down(): void
    {
        foreach (self::answers() as $question => $answer) {
            if ($question === self::OPSEN) {
                DB::table('faqs')->where('pertanyaan', $question)->where('jawaban', $answer)->update(['jawaban' => self::OPSEN_EXAMPLE_ANSWER]);
            } else {
                DB::table('faqs')->where('pertanyaan', $question)->where('jawaban', $answer)->delete();
            }
        }
    }

    /**
     * The reviewed questions and answers, in their order on the portal.
     *
     * @return array<string, string>
     */
    private static function answers(): array
    {
        return [
            'Apa saja yang saya bayar saat membayar pajak kendaraan tahunan?' => implode("\n", [
                'Notis pajak tahunan (SKPD) terdiri atas:',
                '– PKB (Pajak Kendaraan Bermotor) untuk Pemerintah Provinsi NTT: 1,2% dari dasar pengenaan untuk kendaraan pribadi. Dasar pengenaan adalah nilai jual kendaraan dikalikan bobot sesuai jenis kendaraan.',
                '– Opsen PKB untuk kabupaten/kota: 66% dari PKB.',
                '– SWDKLLJ untuk Jasa Raharja, mis. Rp35.000 untuk sepeda motor sampai 250 cc dan Rp143.000 untuk mobil penumpang.',
                '– Denda, bila pembayaran melewati tanggal jatuh tempo.',
                '',
                'Saat perpanjangan STNK lima tahunan ada tambahan biaya penerbitan STNK dan pelat nomor (PNBP Polri).',
            ]),
            self::OPSEN => implode("\n", [
                'Opsen PKB adalah pungutan tambahan sebesar 66% dari Pajak Kendaraan Bermotor (PKB) yang terutang. Opsen berlaku sejak 5 Januari 2025 berdasarkan Undang-Undang Nomor 1 Tahun 2022 tentang Hubungan Keuangan antara Pemerintah Pusat dan Pemerintahan Daerah.',
                '',
                'Opsen dibayar bersamaan dengan PKB dan tercantum sebagai baris tersendiri pada notis pajak. PKB menjadi pendapatan Pemerintah Provinsi NTT, sedangkan opsen langsung masuk ke kas kabupaten/kota tempat kendaraan terdaftar, mis. Pemerintah Kota Kupang, untuk membiayai jalan dan layanan publik di daerah Anda.',
                '',
                'Contoh: bila PKB kendaraan Anda Rp200.000, opsennya Rp132.000. Bersamaan dengan berlakunya opsen, tarif PKB kendaraan pribadi diturunkan dari 1,5% menjadi 1,2%.',
            ]),
            'Berapa denda bila terlambat membayar pajak kendaraan?' => implode("\n", [
                'Denda dihitung sejak lewat tanggal jatuh tempo:',
                '– PKB dan Opsen PKB: 1% per bulan dari pajak yang terutang, paling lama 24 bulan.',
                '– SWDKLLJ: denda Jasa Raharja bertingkat menurut lama terlambat (sampai 3 bulan, 4–6 bulan, 7–9 bulan, dan lebih dari 9 bulan), mis. Rp8.000–Rp32.000 untuk sepeda motor sampai 250 cc dan Rp35.000–Rp100.000 untuk mobil penumpang.',
                '',
                'Selama 1 November–31 Desember 2026, Pergub NTT Nomor 64 Tahun 2026 membebaskan denda PKB dan Opsen PKB serta menghapus pokok tunggakan PKB dan opsennya. Denda SWDKLLJ tahun berjalan tetap dibayar.',
            ]),
            'Bagaimana cara mengetahui besar pajak dan tanggal jatuh tempo kendaraan saya?' => implode("\n", [
                'Tanggal jatuh tempo pajak tahunan sama dengan tanggal dan bulan masa berlaku STNK, dan tercantum pada notis pajak tahun sebelumnya. Pajak dibayar paling lambat pada tanggal itu setiap tahun.',
                '',
                'Besar pajak bisa dicek sebelum berangkat lewat Cek biaya pajak di halaman Beranda atau Layanan PINTU: masukkan nomor polisi dan 5 digit terakhir nomor rangka (tertera di STNK dan BPKB). Hasilnya rincian PKB, opsen, SWDKLLJ, dan denda bila ada. Pengecekan juga bisa dilakukan di aplikasi PRO NTT.',
                '',
                'Hasil cek adalah perkiraan; jumlah yang dibayar adalah yang tercantum pada notis atau kode bayar resmi.',
            ]),
            'Bisakah saya membayar pajak kendaraan secara online?' => implode("\n", [
                'Bisa, untuk pajak tahunan, lewat aplikasi PRO NTT (Pajak Retribusi Online NTT) di ponsel Android. Pilih menu SAMSAT › Cek dan Bayar Pajak, masukkan nomor polisi dan 5 digit terakhir nomor rangka, lalu bayar dengan kode bayar lewat kanal Bank NTT. Setelah itu tukarkan bukti bayar dengan notis pajak di SAMSAT atau Samsat Keliling terdekat. Langkah lengkap beserta video tutorialnya ada di halaman Layanan, bagian Alur Non-Tunai.',
                '',
                'Selama 1 November–31 Desember 2026, pembayaran online mendapat potongan PKB tahun berjalan 5% untuk sepeda motor dan 2% untuk roda empat ke atas.',
                '',
                'Perpanjangan STNK lima tahunan dan layanan yang memerlukan cek fisik tetap dilakukan di loket.',
            ]),
            'Apa akibatnya bila pajak kendaraan tidak dibayar?' => implode("\n", [
                '– Denda bertambah setiap bulan, dan seluruh tunggakan harus dilunasi sekaligus saat pengesahan berikutnya.',
                '– STNK yang tidak disahkan setiap tahun dianggap tidak sah, sehingga pengendara dapat ditilang saat pemeriksaan di jalan.',
                '– Bila masa berlaku STNK lima tahunan habis dan kendaraan tidak didaftarkan ulang paling lama 2 tahun, data registrasinya dapat dihapus dan kendaraan tidak dapat didaftarkan kembali (UU Nomor 22 Tahun 2009 tentang Lalu Lintas dan Angkutan Jalan, Pasal 74).',
                '',
                'Segera lunasi tunggakan, terutama saat ada program keringanan dari Pemerintah Provinsi NTT. Besar tunggakan bisa dicek lewat Cek biaya pajak di PINTU.',
            ]),
        ];
    }
};
