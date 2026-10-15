<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: where a queue ticket is called (D-051 slice 2): the
     * counter calling it, when it was last called and how often at the
     * stage, when it entered the stage (counters call the earliest first,
     * whatever day it was printed) and when it was finished.
     */
    public function up(): void
    {
        Schema::table('queue_tickets', function (Blueprint $table) {
            $table->foreignUlid('queue_counter_id')->nullable()->after('status')->constrained()->nullOnDelete();
            $table->timestamp('dipanggil_pada')->nullable()->after('queue_counter_id');
            $table->unsignedSmallInteger('jumlah_panggil')->default(0)->after('dipanggil_pada');
            $table->timestamp('masuk_tahap_pada')->nullable()->after('jumlah_panggil');
            $table->timestamp('selesai_pada')->nullable()->after('dibatalkan_pada');
            $table->index(['tahap', 'status', 'masuk_tahap_pada']);
        });

        DB::table('queue_tickets')->whereNull('masuk_tahap_pada')->update(['masuk_tahap_pada' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('queue_tickets', function (Blueprint $table) {
            $table->dropIndex(['tahap', 'status', 'masuk_tahap_pada']);
            $table->dropConstrainedForeignId('queue_counter_id');
            $table->dropColumn(['dipanggil_pada', 'jumlah_panggil', 'masuk_tahap_pada', 'selesai_pada']);
        });
    }
};
