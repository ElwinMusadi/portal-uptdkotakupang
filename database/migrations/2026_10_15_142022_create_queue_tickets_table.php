<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: one row per queue number printed at the information
     * desk (D-051). The number counts per service and per WITA day; the
     * tracking code behind the ticket's QR is random so it cannot be guessed.
     * The ticket keeps the stages its service had when it was printed. The
     * plate number stays until an administrator removes it.
     */
    public function up(): void
    {
        Schema::create('queue_tickets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('kode_lacak', 22)->unique();
            $table->foreignUlid('queue_service_id')->constrained()->restrictOnDelete();
            $table->date('tanggal');
            $table->unsignedInteger('urut');
            $table->string('nomor', 12);
            $table->string('nopol', 9)->nullable()->index();
            $table->json('tahap_layanan');
            $table->string('tahap', 20)->nullable();
            $table->string('status', 20);
            $table->string('alasan_batal', 200)->nullable();
            $table->foreignUlid('dicetak_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dibatalkan_pada')->nullable();
            $table->timestamps();
            $table->unique(['queue_service_id', 'tanggal', 'urut']);
            $table->index(['tanggal', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_tickets');
    }
};
