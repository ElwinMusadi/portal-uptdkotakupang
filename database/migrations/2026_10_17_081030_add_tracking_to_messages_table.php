<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations: the random code behind a message's tracking page,
     * /pesan/{kode} (D-058). Messages received before get one too, so their
     * senders can open the page from the ticket number and contact.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('kode_lacak', 22)->nullable()->unique()->after('tiket');
        });

        DB::table('messages')->whereNull('kode_lacak')->select('id')->chunkById(500, function ($messages): void {
            foreach ($messages as $message) {
                DB::table('messages')->where('id', $message->id)->update(['kode_lacak' => Str::random(22)]);
            }
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->string('kode_lacak', 22)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropUnique(['kode_lacak']);
            $table->dropColumn('kode_lacak');
        });
    }
};
