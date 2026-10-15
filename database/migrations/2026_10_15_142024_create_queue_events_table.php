<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: what happened to each queue ticket, by whom and at
     * which stage (D-051), for the taxpayer's tracking page and for finding a
     * ticket's history.
     */
    public function up(): void
    {
        Schema::create('queue_events', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('queue_ticket_id')->constrained()->cascadeOnDelete();
            $table->string('aksi', 30);
            $table->string('tahap', 20)->nullable();
            $table->string('keterangan', 200)->nullable();
            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_events');
    }
};
