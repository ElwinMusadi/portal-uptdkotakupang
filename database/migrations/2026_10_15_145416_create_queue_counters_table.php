<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * One counter per calling stage, serving every service (the owner's
     * answer, D-051): name, stage, and how the TV display reads its name.
     *
     * @var list<array{string, string, string}>
     */
    private const array COUNTERS = [
        ['Loket 1 – Pendaftaran', 'pendaftaran', 'Loket satu, pendaftaran'],
        ['Loket Kwitansi', 'kwitansi', 'Loket kwitansi'],
        ['Loket 2 – Penyerahan', 'penyerahan', 'Loket dua, penyerahan'],
    ];

    /**
     * Run the migrations: the counters that call queue numbers (Antrean
     * Online slice 2, D-051), each at one calling stage and for some or all
     * services (an empty list), with their initial rows.
     */
    public function up(): void
    {
        Schema::create('queue_counters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('nama', 60);
            $table->string('tahap', 20);
            $table->json('layanan');
            $table->string('nama_suara', 80);
            $table->boolean('aktif')->default(true);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });

        $now = now();
        DB::table('queue_counters')->insert(array_map(fn (array $counter, int $urutan): array => [
            'id' => (string) Str::ulid(),
            'nama' => $counter[0],
            'tahap' => $counter[1],
            'layanan' => '[]',
            'nama_suara' => $counter[2],
            'aktif' => true,
            'urutan' => $urutan,
            'created_at' => $now,
            'updated_at' => $now,
        ], self::COUNTERS, array_keys(self::COUNTERS)));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_counters');
    }
};
