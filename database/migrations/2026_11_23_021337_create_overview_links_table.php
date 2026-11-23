<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: the links to the Ikhtisar page an administrator
     * made (D-099). Each holds a random token shown once; only its hash is
     * kept, so a link can be revoked but its token never read back.
     */
    public function up(): void
    {
        Schema::create('overview_links', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('nama', 60);
            $table->char('token_hash', 64)->unique();
            $table->timestamp('terakhir_dibuka')->nullable();
            $table->foreignUlid('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overview_links');
    }
};
