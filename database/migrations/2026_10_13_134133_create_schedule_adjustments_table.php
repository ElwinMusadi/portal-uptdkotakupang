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
        Schema::create('schedule_adjustments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->date('tanggal')->unique();
            $table->string('jenis', 12);
            $table->string('mulai', 5)->default('');
            $table->string('selesai', 5)->default('');
            $table->string('keterangan', 150);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_adjustments');
    }
};
