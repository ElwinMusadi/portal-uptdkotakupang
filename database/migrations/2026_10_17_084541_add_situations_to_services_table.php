<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The citizens' situations each initial service answers, for the guide
     * "Layanan apa yang saya perlukan?" on the Layanan page (D-059), by slug.
     * Staff change them in Dashboard › Persyaratan Layanan.
     *
     * @var array<string, list<string>>
     */
    private const array INITIAL = [
        'pajak-tahunan' => ['Membayar pajak kendaraan tahun ini', 'Mengesahkan STNK tahunan'],
        'lima-tahun' => ['STNK sudah lima tahun dan pelat nomor perlu diganti'],
        'mutasi-tetap' => ['Pindah ke luar Kota Kupang dan membawa kendaraan sendiri'],
        'mutasi-bbn' => ['Menjual kendaraan kepada pembeli di luar Kota Kupang', 'Membeli kendaraan dari Kota Kupang untuk dibawa ke daerah lain'],
        'duplikat' => ['STNK hilang', 'STNK rusak atau tidak terbaca'],
        'badan-hukum' => ['Kendaraan atas nama perusahaan atau badan hukum'],
    ];

    /**
     * Run the migrations: the situations a service answers, as citizens
     * would put them (D-059). Services without any are offered by name.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->text('situasi')->nullable()->after('persyaratan');
        });

        DB::table('services')->update(['situasi' => '[]']);
        foreach (self::INITIAL as $slug => $situations) {
            DB::table('services')->where('slug', $slug)->update(['situasi' => json_encode($situations)]);
        }

        Schema::table('services', function (Blueprint $table) {
            $table->text('situasi')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('situasi');
        });
    }
};
