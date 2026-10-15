<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: the ticket's place before and after a station
     * marked it (D-051 slice 3), so the employee can undo a scan right away.
     */
    public function up(): void
    {
        Schema::table('queue_events', function (Blueprint $table) {
            $table->json('keadaan')->nullable()->after('otomatis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('queue_events', function (Blueprint $table) {
            $table->dropColumn('keadaan');
        });
    }
};
