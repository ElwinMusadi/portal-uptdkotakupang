<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: the transactions of the imported STS files (D-047),
     * without the owner's name, the plate number, the numbering or the
     * address below the kelurahan. The "sidik" is a keyed hash of the plate
     * number and the transaction, so a transaction imported twice is saved
     * once. Amounts are whole rupiah.
     */
    public function up(): void
    {
        Schema::create('revenue_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('revenue_import_id')->constrained()->cascadeOnDelete();
            $table->string('loket', 80);
            $table->date('tanggal');
            $table->string('jenis_daftar', 80);
            $table->string('jenis_kendaraan', 40);
            $table->string('kepemilikan', 20);
            $table->string('merk', 40);
            $table->string('warna_plat', 20);
            $table->string('upt_asal', 80);
            $table->string('kecamatan', 80);
            $table->string('kelurahan', 80);
            $table->unsignedSmallInteger('tahun_tunggakan');

            foreach (['pkb', 'bbn', 'denda_pkb', 'denda_bbn', 'tunggakan', 'denda_tunggakan', 'opsen_pkb', 'opsen_bbn',
                'opsen_denda_pkb', 'opsen_denda_bbn', 'opsen_tunggakan', 'opsen_denda_tunggakan', 'jumlah'] as $amount) {
                $table->unsignedBigInteger($amount)->default(0);
            }

            $table->char('sidik', 64)->unique();
            $table->index(['tanggal', 'loket']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenue_transactions');
    }
};
