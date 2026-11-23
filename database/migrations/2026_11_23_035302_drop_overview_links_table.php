<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: the Ikhtisar page is opened and closed by one
     * switch at /laporan-penerimaan (D-100), so the links with a token of
     * D-099 go, and every link made before stops working.
     */
    public function up(): void
    {
        Schema::dropIfExists('overview_links');
    }

    /**
     * Reverse the migrations: the table as D-099 made it, without its links.
     */
    public function down(): void
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
};
