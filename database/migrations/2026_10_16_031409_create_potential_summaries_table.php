<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: the vehicles of an import counted once when it
     * finishes (D-052), per due month, kelurahan, wheel class, compliance
     * group, years in arrears, type and visit outcome, with their amounts. The
     * map and tables read these few thousand rows instead of every vehicle,
     * so a year of data stays quick.
     */
    public function up(): void
    {
        Schema::create('potential_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('potential_import_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('bulan')->nullable();
            $table->string('kecamatan', 40);
            $table->string('kelurahan', 80);
            $table->string('roda', 2)->nullable();
            $table->string('kelompok', 20);
            $table->unsignedSmallInteger('tahun_tunggak');
            $table->string('jenis', 40);
            $table->string('hasil_kunjungan', 200);
            $table->unsignedInteger('kendaraan');
            $table->unsignedBigInteger('pokok_pajak');
            $table->unsignedBigInteger('denda_berjalan');
            $table->unsignedBigInteger('pokok_tunggak');
            $table->unsignedBigInteger('denda_tunggak');
            $table->unsignedBigInteger('tagihan');
            $table->index(['potential_import_id', 'bulan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('potential_summaries');
    }
};
