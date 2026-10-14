<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. "induk" is checked by the application rather than
     * a foreign key, so a unit and the units under it can be deleted
     * together.
     */
    public function up(): void
    {
        Schema::create('organization_units', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('nama', 120);
            $table->string('singkatan', 3);
            $table->string('jenis', 12);
            $table->ulid('induk')->nullable()->index();
            $table->string('jabatan', 120)->default('');
            $table->string('pejabat', 160)->default('');
            $table->string('nip', 18)->default('');
            $table->string('foto', 500)->nullable();
            $table->text('anggota');
            $table->text('uraian_tugas');
            $table->string('catatan', 200)->default('');
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_units');
    }
};
