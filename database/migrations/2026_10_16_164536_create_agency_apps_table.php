<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: the apps and websites of BPAD and the UPTD shown on
     * the portal's Aplikasi & Situs page (D-054).
     */
    public function up(): void
    {
        Schema::create('agency_apps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('nama', 60);
            $table->string('nama_lengkap', 160)->default('');
            $table->string('keterangan', 300)->default('');
            $table->string('jenis', 10);
            $table->string('pengelola', 10);
            $table->string('tautan', 500);
            $table->string('logo', 500)->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_apps');
    }
};
