<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: the revenue target of each year (D-068), one per
     * kind of revenue the realisation report (Form I) compares against, and
     * the realisation entered by hand for the months before the STS imports
     * start ("saldo awal", through "saldo_sampai_bulan"). Amounts are whole
     * rupiah.
     */
    public function up(): void
    {
        Schema::create('revenue_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun')->unique();

            foreach (['target_pkb', 'target_bbn', 'target_denda_pkb', 'target_denda_bbn'] as $target) {
                $table->unsignedBigInteger($target)->default(0);
            }

            $table->unsignedTinyInteger('saldo_sampai_bulan')->nullable();

            foreach (['saldo_pkb', 'saldo_tunggakan', 'saldo_bbn', 'saldo_denda_pkb', 'saldo_denda_tunggakan', 'saldo_denda_bbn'] as $balance) {
                $table->unsignedBigInteger($balance)->default(0);
            }

            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenue_targets');
    }
};
