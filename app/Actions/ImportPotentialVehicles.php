<?php

namespace App\Actions;

use App\Enums\DashboardModule;
use App\Models\PotentialImport;
use App\Models\PotentialVehicle;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Imports BPAD's potential-tax file for Pemetaan PKB (D-052) in parts: the
 * dashboard announces how many rows the file has, sends them a part at a
 * time in the file's order, then finishes. The finished import replaces the
 * data before it (the owner's decision); starting an import drops any import
 * left unfinished.
 */
class ImportPotentialVehicles
{
    /**
     * Rows per part sent by the dashboard, at most.
     */
    public const int ROWS_PER_PART = 1000;

    /**
     * Rows per insert, under SQLite's limit on bound values.
     */
    private const int ROWS_PER_INSERT = 200;

    /**
     * Rows deleted at once when older data goes.
     */
    private const int ROWS_PER_DELETE = 5000;

    public function __construct(private ReadPotentialRows $readRows, private BuildPotentialSummary $summary, private RecordActivity $recordActivity) {}

    /**
     * Start an import of a file with this many rows.
     */
    public function start(User $user, string $file, int $rows): PotentialImport
    {
        PotentialImport::query()->unfinished()->get()->each(fn (PotentialImport $import) => $this->remove($import));

        return PotentialImport::query()->create(['berkas' => $file, 'jumlah' => $rows, 'diimpor_oleh' => $user->getKey()]);
    }

    /**
     * Save the next part of the file's rows, the first being the file's row
     * after the ones already received.
     *
     * @param  list<list<string>>  $rows
     *
     * @throws ValidationException when the import is finished, the part is out of order or too long, or a row cannot be read
     */
    public function add(PotentialImport $import, int $first, array $rows): PotentialImport
    {
        return DB::transaction(function () use ($import, $first, $rows): PotentialImport {
            $import = PotentialImport::query()->lockForUpdate()->findOrFail($import->getKey());

            $problem = match (true) {
                $import->selesai_pada !== null => 'Impor ini sudah selesai.',
                $first !== $import->diterima => 'Bagian berkas tidak berurutan: berikutnya baris '.($import->diterima + 1).'.',
                $import->diterima + count($rows) > $import->jumlah => "Berkas berisi lebih dari {$import->jumlah} baris yang disebutkan.",
                default => null,
            };
            if ($problem !== null) {
                throw ValidationException::withMessages(['baris' => $problem]);
            }

            $saved = $this->readRows->handle($rows, $first, $import->getKey());
            foreach (array_chunk($saved, self::ROWS_PER_INSERT) as $chunk) {
                PotentialVehicle::query()->insert($chunk);
            }
            $import->update([
                'diterima' => $import->diterima + count($rows),
                'tanggal_penetapan' => $import->tanggal_penetapan ?? collect($saved)->pluck('tanggal_penetapan')->filter()->first(),
            ]);

            return $import;
        });
    }

    /**
     * Finish the import once every row arrived: its summary is counted and
     * it replaces the data before it.
     *
     * @throws ValidationException when the import is finished or rows are missing
     */
    public function finish(PotentialImport $import, User $user): PotentialImport
    {
        $import = DB::transaction(function () use ($import, $user): PotentialImport {
            $import = PotentialImport::query()->lockForUpdate()->findOrFail($import->getKey());

            $problem = match (true) {
                $import->selesai_pada !== null => 'Impor ini sudah selesai.',
                $import->diterima !== $import->jumlah => "Baru {$import->diterima} dari {$import->jumlah} baris yang diterima.",
                default => null,
            };
            if ($problem !== null) {
                throw ValidationException::withMessages(['impor' => $problem]);
            }

            $this->summary->store($import);
            $import->update(['selesai_pada' => now()]);
            $this->recordActivity->handle($user, DashboardModule::Pemetaan, 'mengimpor', $this->title($import));

            return $import;
        });

        PotentialImport::query()->whereKeyNot($import->getKey())->get()->each(fn (PotentialImport $older) => $this->remove($older));

        return $import;
    }

    /**
     * Delete the data Pemetaan PKB shows, e.g. on request of a taxpayer or
     * to start over.
     */
    public function delete(PotentialImport $import, User $user): void
    {
        $this->recordActivity->handle($user, DashboardModule::Pemetaan, 'menghapus', $this->title($import));
        $this->remove($import);
    }

    /**
     * "Data potensi PKB penetapan 16 Okt 2026 (28.725 kendaraan)".
     */
    public function title(PotentialImport $import): string
    {
        $date = $import->tanggal_penetapan === null ? '' : ' penetapan '.Carbon::parse($import->tanggal_penetapan)->locale('id')->translatedFormat('j M Y');

        return 'Data potensi PKB'.$date.' ('.number_format($import->jumlah, 0, ',', '.').' kendaraan)';
    }

    /**
     * Remove an import and its rows, a few thousand rows at a time.
     */
    private function remove(PotentialImport $import): void
    {
        while (PotentialVehicle::query()->whereBelongsTo($import, 'import')->limit(self::ROWS_PER_DELETE)->delete() > 0) {
            // keep deleting
        }
        $import->delete();
    }
}
