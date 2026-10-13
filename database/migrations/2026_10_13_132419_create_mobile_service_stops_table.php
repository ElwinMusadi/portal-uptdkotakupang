<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mobile_service_stops', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedTinyInteger('hari');
            $table->string('mulai', 5);
            $table->string('selesai', 5);
            $table->string('lokasi', 120);
            $table->string('kecamatan', 60);
            $table->string('layanan', 80);
            $table->string('catatan', 300)->default('');
            $table->boolean('aktif')->default(true);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
            $table->index(['aktif', 'hari', 'mulai']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_service_stops');
    }
};
