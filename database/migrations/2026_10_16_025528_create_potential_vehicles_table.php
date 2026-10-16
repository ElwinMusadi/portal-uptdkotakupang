<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: every vehicle of the potential-tax file with all its
     * columns, the owner's identity included, for the collection lists in the
     * dashboard (D-052, the owner's decision). The identity card and phone
     * numbers are encrypted. The kecamatan follows the official list of the
     * kelurahan, since the file's own column is not consistent; the wheel
     * class, the compliance group, the due month and the amount due are worked
     * out once at import.
     */
    public function up(): void
    {
        Schema::create('potential_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('potential_import_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('urut');
            $table->string('nopol', 12);
            $table->string('nama', 150);
            $table->text('ktp')->nullable();
            $table->text('telepon')->nullable();
            $table->string('alamat', 255);
            $table->string('rt', 10);
            $table->string('rw', 10);
            $table->string('kelurahan', 80);
            $table->string('kecamatan', 40);
            $table->string('kecamatan_berkas', 80);
            $table->string('jenis', 40);
            $table->string('roda', 2)->nullable();
            $table->string('merk', 60);
            $table->string('tipe', 120);
            $table->string('mesin', 40);
            $table->string('rangka', 40);
            $table->unsignedSmallInteger('tahun_buat')->nullable();
            $table->string('warna_plat', 20);
            $table->string('warna_kendaraan', 60);
            $table->string('bbm', 30);
            $table->date('tanggal_penetapan')->nullable();
            $table->string('upt_asal', 80);
            $table->string('penggunaan', 60);
            $table->unsignedInteger('cyl')->nullable();
            $table->string('kode_jr', 10);
            $table->date('sd_stnk')->nullable();
            $table->date('sd_notice')->nullable();
            $table->unsignedTinyInteger('bulan_jatuh_tempo')->nullable();
            $table->string('rubah_bentuk', 80);
            $table->unsignedBigInteger('njkb');
            $table->unsignedBigInteger('njub');
            $table->decimal('bobot', 6, 3)->nullable();
            $table->unsignedSmallInteger('beda_hari');
            $table->unsignedSmallInteger('beda_bulan');
            $table->unsignedSmallInteger('bulat_bulan');
            $table->unsignedSmallInteger('tahun_tunggak');
            $table->unsignedSmallInteger('tahun_tunggak_awal');
            $table->unsignedBigInteger('pokok_pajak');
            $table->unsignedBigInteger('denda_berjalan');
            $table->unsignedBigInteger('pokok_tunggak');
            $table->unsignedBigInteger('denda_tunggak');
            $table->unsignedBigInteger('tagihan');
            $table->string('kepatuhan', 120);
            $table->string('kelompok', 20);
            $table->string('peringatan', 200);
            $table->string('dtd_status', 200);
            $table->string('dtd_kegiatan', 20);
            $table->string('dtd_petugas', 120);
            $table->index(['potential_import_id', 'kecamatan', 'kelurahan']);
            $table->index(['potential_import_id', 'tagihan']);
            $table->index(['potential_import_id', 'nopol']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('potential_vehicles');
    }
};
