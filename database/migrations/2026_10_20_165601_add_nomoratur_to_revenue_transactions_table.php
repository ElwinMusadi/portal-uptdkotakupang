<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: the SKPD form number of each STS transaction
     * (D-071), seven digits with the leading zeros the export can drop put
     * back, or "-" when the file has none (Mutasi keluar, Tambahan pajak).
     * Transactions imported before it stay null until their file is imported
     * again.
     */
    public function up(): void
    {
        Schema::table('revenue_transactions', function (Blueprint $table) {
            $table->string('nomoratur', 7)->nullable()->after('kelurahan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revenue_transactions', function (Blueprint $table) {
            $table->dropColumn('nomoratur');
        });
    }
};
