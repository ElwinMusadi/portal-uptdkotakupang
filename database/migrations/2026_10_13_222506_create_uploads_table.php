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
        Schema::create('uploads', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tujuan', 20);
            $table->boolean('publik');
            $table->string('path')->unique();
            $table->string('nama');
            $table->string('tipe', 120);
            $table->unsignedBigInteger('ukuran');
            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('dipakai_pada')->nullable();
            $table->timestamps();

            $table->index(['dipakai_pada', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploads');
    }
};
