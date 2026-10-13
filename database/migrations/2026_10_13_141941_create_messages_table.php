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
        Schema::create('messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tiket', 20)->unique();
            $table->string('jenis', 12);
            $table->string('nama', 100)->default('');
            $table->string('kontak', 120)->default('');
            $table->string('layanan', 80);
            $table->text('isi');
            $table->string('sumber', 12);
            $table->string('status', 10)->default('baru');
            $table->timestamp('diterima');
            $table->string('ditangani', 120)->default('');
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
            $table->index(['status', 'diterima']);
        });
    }

    /**
     * Reverse the migrations. This discards every message and its responses.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
