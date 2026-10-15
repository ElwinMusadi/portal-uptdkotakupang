<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: the counter of a call, and whether the system did
     * it on its own (D-051 slice 2): a stage passed without being marked, or
     * a called number put aside when its counter called the next one.
     */
    public function up(): void
    {
        Schema::table('queue_events', function (Blueprint $table) {
            $table->foreignUlid('queue_counter_id')->nullable()->after('keterangan')->constrained()->nullOnDelete();
            $table->boolean('otomatis')->default(false)->after('queue_counter_id');
            $table->index(['aksi', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('queue_events', function (Blueprint $table) {
            $table->dropIndex(['aksi', 'created_at']);
            $table->dropConstrainedForeignId('queue_counter_id');
            $table->dropColumn('otomatis');
        });
    }
};
