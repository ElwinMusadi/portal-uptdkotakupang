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
        Schema::create('update_standards', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('konten', 240);
            $table->string('modul', 20)->index();
            $table->string('unit', 240);
            $table->string('frekuensi', 240);
            $table->unsignedSmallInteger('interval_hari');
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('update_standards');
    }
};
