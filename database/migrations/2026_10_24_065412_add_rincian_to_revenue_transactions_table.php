<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: the STS columns the report does not use, among
     * them the plate number, the owner's name and the RT/RW, for the
     * dashboard's Rincian penerimaan (D-079). They are kept encrypted, as one
     * JSON document, for the transactions of the last 7 days only; null when
     * the transaction was imported before it or is older.
     */
    public function up(): void
    {
        Schema::table('revenue_transactions', function (Blueprint $table) {
            $table->text('rincian')->nullable()->after('nomoratur');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revenue_transactions', function (Blueprint $table) {
            $table->dropColumn('rincian');
        });
    }
};
