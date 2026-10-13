<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: the employee's own profile in the dashboard's
     * Akun tab (D-027), empty until the employee fills it in.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('jabatan', 240)->default('')->after('email');
            $table->string('unit', 240)->default('')->after('jabatan');
            $table->string('peran_informasi', 40)->default('')->after('unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['jabatan', 'unit', 'peran_informasi']);
        });
    }
};
