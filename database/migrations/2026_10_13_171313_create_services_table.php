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
        Schema::create('services', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('nama', 120);
            $table->string('slug', 80)->unique();
            $table->string('ringkasan', 200);
            $table->string('loket', 60);
            $table->string('jenis', 10)->default('layanan');
            $table->boolean('cek_fisik')->default(false);
            $table->boolean('non_tunai')->default(false);
            $table->boolean('diwakilkan')->default(false);
            $table->text('label');
            $table->text('persyaratan');
            $table->text('catatan');
            $table->boolean('unggulan')->default(false);
            $table->unsignedInteger('urutan')->default(0);
            $table->string('status', 12)->default('draf');
            $table->string('penyedia', 120);
            $table->string('pemeriksa', 120)->default('');
            $table->text('catatan_revisi');
            $table->timestamp('terbit_pada')->nullable();
            $table->text('riwayat');
            $table->timestamps();
            $table->index(['status', 'urutan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
