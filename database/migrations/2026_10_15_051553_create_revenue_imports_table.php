<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: one row per STS file imported for a counter (D-047),
     * with the counter's key and its name at the time, the dates and totals of
     * the transactions saved, and whether the portal may show them. The file
     * itself is not kept.
     */
    public function up(): void
    {
        Schema::create('revenue_imports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('loket', 80);
            $table->string('nama_loket', 120);
            $table->string('nama_berkas', 160);
            $table->date('tanggal_awal');
            $table->date('tanggal_akhir');
            $table->unsignedInteger('jumlah_transaksi');
            $table->unsignedInteger('jumlah_dilewati');
            $table->unsignedBigInteger('total');
            $table->boolean('tampil')->default(false);
            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['tanggal_awal', 'tanggal_akhir']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenue_imports');
    }
};
