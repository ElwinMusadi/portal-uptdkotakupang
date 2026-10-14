<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Turn the weekly Samsat Keliling stops into dated service outside the
     * office (D-043): each stop gets a kind, a Samsat Keliling unit or the
     * name of an outlet or counter, an optional date range and its weekdays
     * (0 = Sunday). Every former weekly stop becomes a Samsat Keliling 01
     * stop on the next such weekday, switched off until it is checked.
     */
    public function up(): void
    {
        Schema::table('mobile_service_stops', function (Blueprint $table) {
            $table->string('jenis', 10)->default('keliling')->after('id');
            $table->string('unit', 2)->nullable()->after('jenis');
            $table->string('nama', 120)->default('')->after('unit');
            $table->date('tanggal_mulai')->nullable()->after('nama');
            $table->date('tanggal_selesai')->nullable()->after('tanggal_mulai');
            $table->text('hari_layanan')->nullable()->after('tanggal_selesai');
        });

        $today = CarbonImmutable::now('Asia/Makassar')->startOfDay();

        foreach (DB::table('mobile_service_stops')->get(['id', 'hari']) as $stop) {
            $daysAhead = ((int) $stop->hari - $today->dayOfWeek + 7) % 7 ?: 7;
            $date = $today->addDays($daysAhead);

            DB::table('mobile_service_stops')->where('id', $stop->id)->update([
                'jenis' => 'keliling',
                'unit' => '01',
                'tanggal_mulai' => $date->toDateString(),
                'tanggal_selesai' => $date->toDateString(),
                'hari_layanan' => json_encode([$date->dayOfWeek]),
                'aktif' => false,
            ]);
        }

        Schema::table('mobile_service_stops', function (Blueprint $table) {
            $table->dropIndex(['aktif', 'hari', 'mulai']);
            $table->dropColumn('hari');
        });

        Schema::table('mobile_service_stops', function (Blueprint $table) {
            $table->renameColumn('hari_layanan', 'hari');
            $table->index(['aktif', 'tanggal_mulai', 'tanggal_selesai']);
        });
    }

    /**
     * Reverse the migrations: each stop keeps its first weekday.
     */
    public function down(): void
    {
        Schema::table('mobile_service_stops', function (Blueprint $table) {
            $table->unsignedTinyInteger('hari_lama')->default(1)->after('id');
        });

        foreach (DB::table('mobile_service_stops')->get(['id', 'hari']) as $stop) {
            $days = json_decode((string) $stop->hari, true);

            DB::table('mobile_service_stops')->where('id', $stop->id)->update([
                'hari_lama' => is_array($days) && is_int($days[0] ?? null) ? $days[0] : 1,
            ]);
        }

        Schema::table('mobile_service_stops', function (Blueprint $table) {
            $table->dropIndex(['aktif', 'tanggal_mulai', 'tanggal_selesai']);
            $table->dropColumn(['jenis', 'unit', 'nama', 'tanggal_mulai', 'tanggal_selesai', 'hari']);
        });

        Schema::table('mobile_service_stops', function (Blueprint $table) {
            $table->renameColumn('hari_lama', 'hari');
            $table->index(['aktif', 'hari', 'mulai']);
        });
    }
};
