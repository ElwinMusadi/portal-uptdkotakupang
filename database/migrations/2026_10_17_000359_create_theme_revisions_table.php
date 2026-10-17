<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: the last five themes saved in Pengaturan ›
     * Tampilan (D-057), each with who saved it and when, so an administrator
     * can see the changes and bring an earlier theme back.
     */
    public function up(): void
    {
        Schema::create('theme_revisions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->json('tema');
            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_revisions');
    }
};
