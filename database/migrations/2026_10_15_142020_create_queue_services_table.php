<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * The services of the owner's flowchart and answers (D-051): code, name,
     * what the service covers, and its stages in flow order. Administrators
     * adjust them in the dashboard.
     *
     * @var list<array{string, string, string, list<string>}>
     */
    private const array SERVICES = [
        ['A', 'Registrasi Ulang', 'Registrasi Ulang/Perpanjangan STNK 5 Tahun, Duplikat STNK',
            ['pendaftaran', 'penetapan', 'kwitansi', 'pembayaran', 'cetak-skpd', 'cetak-stnk', 'penyerahan']],
        ['B', 'Pengesahan', 'Pajak Tahunan/Pengesahan STNK',
            ['pendaftaran', 'penetapan', 'kwitansi', 'pembayaran', 'cetak-skpd', 'cetak-stnk', 'penyerahan']],
        ['C', 'BBN/Mutasi', 'Bea Balik Nama dan Mutasi',
            ['pendaftaran', 'penetapan', 'kwitansi', 'pembayaran', 'cetak-skpd', 'penyerahan']],
        ['D', 'Pembayaran Online', 'Pajak yang sudah dibayar secara online',
            ['pendaftaran', 'penetapan', 'kwitansi', 'pembayaran', 'cetak-stnk', 'penyerahan']],
    ];

    /**
     * Run the migrations: the services a queue number is printed for at the
     * information desk (Antrean Online, D-051), with their initial rows.
     */
    public function up(): void
    {
        Schema::create('queue_services', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->char('kode', 1)->unique();
            $table->string('nama', 80);
            $table->string('cakupan', 200)->default('');
            $table->json('tahap');
            $table->boolean('aktif')->default(true);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });

        $now = now();
        DB::table('queue_services')->insert(array_map(fn (array $service, int $urutan): array => [
            'id' => (string) Str::ulid(),
            'kode' => $service[0],
            'nama' => $service[1],
            'cakupan' => $service[2],
            'tahap' => json_encode($service[3]),
            'aktif' => true,
            'urutan' => $urutan,
            'created_at' => $now,
            'updated_at' => $now,
        ], self::SERVICES, array_keys(self::SERVICES)));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_services');
    }
};
