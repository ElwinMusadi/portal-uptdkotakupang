<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * The list of places for service outside the office (D-044), picked when
     * a schedule entry is filled in. It starts with the places already in
     * the schedule.
     */
    public function up(): void
    {
        Schema::create('service_locations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('nama', 120)->unique();
            $table->string('kecamatan', 60);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });

        $now = now();
        $names = [];

        foreach (DB::table('mobile_service_stops')->orderBy('created_at')->orderBy('id')->get(['lokasi', 'kecamatan']) as $order => $stop) {
            $key = mb_strtolower(trim($stop->lokasi));

            if ($key === '' || isset($names[$key])) {
                continue;
            }

            $names[$key] = true;
            DB::table('service_locations')->insert([
                'id' => strtolower((string) Str::ulid()),
                'nama' => trim($stop->lokasi),
                'kecamatan' => $stop->kecamatan,
                'urutan' => count($names) - 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_locations');
    }
};
