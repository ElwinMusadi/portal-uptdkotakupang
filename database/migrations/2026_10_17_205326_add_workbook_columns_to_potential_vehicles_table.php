<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: the columns the .xlsx Data Potensi workbook adds
     * (D-064). Every column is kept (D-052): the UPT and kecamatan codes, the
     * tariff, the total, the "NOR" amounts (apparently before relief) and the
     * time the data was drawn. The map and the amount due still use the usual
     * amounts.
     */
    public function up(): void
    {
        Schema::table('potential_vehicles', function (Blueprint $table) {
            $table->string('kode_upt', 10)->default('');
            $table->string('kode_kec', 20)->default('');
            $table->decimal('tarif', 8, 5)->nullable();
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedBigInteger('pokok_pajak_nor')->default(0);
            $table->unsignedBigInteger('denda_berjalan_nor')->default(0);
            $table->unsignedBigInteger('pokok_tunggak_nor')->default(0);
            $table->unsignedBigInteger('denda_tunggak_nor')->default(0);
            $table->unsignedBigInteger('total_nor')->default(0);
            $table->timestamp('ditarik_pada')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('potential_vehicles', function (Blueprint $table) {
            $table->dropColumn(['kode_upt', 'kode_kec', 'tarif', 'total', 'pokok_pajak_nor', 'denda_berjalan_nor', 'pokok_tunggak_nor', 'denda_tunggak_nor', 'total_nor', 'ditarik_pada']);
        });
    }
};
