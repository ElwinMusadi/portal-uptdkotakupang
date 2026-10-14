<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. The point and radius decide where officers may
     * clock in; the date (WITA) decides when.
     */
    public function up(): void
    {
        Schema::create('field_activities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('nama', 160);
            $table->date('tanggal')->index();
            $table->string('tempat', 160);
            $table->decimal('lat', 9, 6);
            $table->decimal('lng', 9, 6);
            $table->unsignedSmallInteger('radius');
            $table->string('catatan', 300)->default('');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_activities');
    }
};
