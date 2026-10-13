<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. The dashboard activity log for content kept on the
     * server: who did what to which entry, in which module. Entries are only
     * ever added.
     */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained();
            $table->string('module', 20);
            $table->string('action', 60);
            $table->string('target');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['module', 'created_at']);
        });
    }

    /**
     * Reverse the migrations. This discards the activity log.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
