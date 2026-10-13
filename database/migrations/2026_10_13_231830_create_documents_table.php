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
        Schema::create('documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('judul', 200);
            $table->string('keterangan', 300)->default('');
            $table->string('kategori', 10);
            $table->string('format', 6);
            $table->string('nomor', 60)->default('');
            $table->string('tahun', 4)->default('');
            $table->text('berkas')->nullable();
            $table->string('tautan', 500)->default('');
            $table->unsignedInteger('urutan')->default(0);
            $table->string('status', 12)->default('draf');
            $table->string('penyedia', 120);
            $table->string('pemeriksa', 120)->default('');
            $table->text('catatan_revisi');
            $table->timestamp('terbit_pada')->nullable();
            $table->text('riwayat');
            $table->timestamps();
            $table->index(['status', 'kategori', 'urutan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
