<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: one row per import of BPAD's potential-tax file for
     * Pemetaan PKB (D-052). The file is read in the browser and sent in parts;
     * an import is in progress until every row arrived, and the finished one
     * replaces the data before it (the owner's decision).
     */
    public function up(): void
    {
        Schema::create('potential_imports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('berkas', 200);
            $table->unsignedInteger('jumlah');
            $table->unsignedInteger('diterima')->default(0);
            $table->date('tanggal_penetapan')->nullable();
            $table->foreignUlid('diimpor_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('selesai_pada')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('potential_imports');
    }
};
