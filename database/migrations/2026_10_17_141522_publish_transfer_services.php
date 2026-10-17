<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Publishes the change-of-owner and transfer-in services added as drafts
 * (D-061), at the owner's request (D-062), so the Layanan page and its guide
 * offer them. Staff can still correct their requirements in Dashboard ›
 * Persyaratan Layanan.
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const array SLUGS = ['balik-nama', 'mutasi-masuk'];

    /**
     * The workflow step this migration records first in the history.
     */
    private const string STEP = 'menerbitkan atas keputusan pemilik';

    /**
     * Run the migrations: only services still in draft are published; one
     * staff have since moved along the workflow is left as it is.
     */
    public function up(): void
    {
        $now = now();

        foreach (DB::table('services')->whereIn('slug', self::SLUGS)->where('status', 'draf')->get(['id', 'riwayat']) as $service) {
            DB::table('services')->where('id', $service->id)->update([
                'status' => 'terbit',
                'terbit_pada' => $now,
                'riwayat' => json_encode([
                    ['waktu' => $now->toIso8601String(), 'oleh' => 'PINTU', 'aksi' => self::STEP, 'ke' => 'terbit'],
                    ...(json_decode($service->riwayat, true) ?: []),
                ]),
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations: a service whose latest step is still this
     * migration's goes back to draft.
     */
    public function down(): void
    {
        foreach (DB::table('services')->whereIn('slug', self::SLUGS)->where('status', 'terbit')->get(['id', 'riwayat']) as $service) {
            $history = json_decode($service->riwayat, true) ?: [];

            if (($history[0]['aksi'] ?? null) !== self::STEP) {
                continue;
            }

            DB::table('services')->where('id', $service->id)->update([
                'status' => 'draf',
                'terbit_pada' => null,
                'riwayat' => json_encode(array_slice($history, 1)),
            ]);
        }
    }
};
