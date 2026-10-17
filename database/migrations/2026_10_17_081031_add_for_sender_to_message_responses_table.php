<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: whether the sender sees a response on the
     * message's tracking page (D-058). Responses recorded before stay
     * internal notes.
     */
    public function up(): void
    {
        Schema::table('message_responses', function (Blueprint $table) {
            $table->boolean('untuk_pelapor')->default(false)->after('isi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_responses', function (Blueprint $table) {
            $table->dropColumn('untuk_pelapor');
        });
    }
};
