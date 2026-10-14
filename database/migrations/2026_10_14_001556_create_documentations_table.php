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
        Schema::create('documentations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('judul', 160);
            $table->string('kategori', 12);
            $table->string('lokasi', 120);
            $table->date('tanggal');
            $table->string('deskripsi', 600)->default('');
            $table->text('foto');
            $table->string('status', 12)->default('draf');
            $table->string('penyedia', 120);
            $table->string('pemeriksa', 120)->default('');
            $table->text('catatan_revisi');
            $table->timestamp('terbit_pada')->nullable();
            $table->text('riwayat');
            $table->timestamps();
            $table->index(['status', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentations');
    }
};
