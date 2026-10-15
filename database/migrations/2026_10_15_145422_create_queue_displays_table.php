<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: the waiting-room TV displays an administrator set
     * up (D-051 slice 2). Each holds a random token shown once; only its hash
     * is kept, so a display can be revoked but its token never read back.
     */
    public function up(): void
    {
        Schema::create('queue_displays', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('nama', 60);
            $table->char('token_hash', 64)->unique();
            $table->timestamp('terakhir_terlihat')->nullable();
            $table->foreignUlid('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_displays');
    }
};
