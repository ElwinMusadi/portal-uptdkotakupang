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
        Schema::create('articles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('judul', 160);
            $table->string('slug', 80)->unique();
            $table->string('kategori', 12);
            $table->string('ringkasan', 240);
            $table->mediumText('isi');
            $table->string('sampul', 500)->nullable();
            $table->string('keterangan_sampul', 200)->default('');
            $table->string('kredit_foto', 120)->default('');
            $table->date('tanggal');
            $table->boolean('sorotan')->default(false);
            $table->boolean('pengumuman_beranda')->default(false);
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
        Schema::dropIfExists('articles');
    }
};
