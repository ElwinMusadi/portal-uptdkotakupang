<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: the opening balance of Form I.I, the realisation of
     * the vehicles registered at the city's UPT (D-101), over the same months
     * as the combined one ("saldo_sampai_bulan"). Null until entered: a year
     * saved before D-101 has a combined opening balance only.
     */
    public function up(): void
    {
        Schema::table('revenue_targets', function (Blueprint $table) {
            foreach (['pkb', 'tunggakan', 'bbn', 'denda_pkb', 'denda_tunggakan', 'denda_bbn'] as $part) {
                $table->unsignedBigInteger("saldo_kota_{$part}")->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revenue_targets', function (Blueprint $table) {
            $table->dropColumn(array_map(
                fn (string $part): string => "saldo_kota_{$part}",
                ['pkb', 'tunggakan', 'bbn', 'denda_pkb', 'denda_tunggakan', 'denda_bbn'],
            ));
        });
    }
};
