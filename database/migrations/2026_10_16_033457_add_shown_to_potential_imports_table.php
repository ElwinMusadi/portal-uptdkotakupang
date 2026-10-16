<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: whether the import's figures appear on the public
     * portal (D-052 slice 2). An Administrator turns it on once the data is
     * checked; a new import starts hidden.
     */
    public function up(): void
    {
        Schema::table('potential_imports', function (Blueprint $table) {
            $table->boolean('tampil')->default(false)->after('selesai_pada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('potential_imports', function (Blueprint $table) {
            $table->dropColumn('tampil');
        });
    }
};
