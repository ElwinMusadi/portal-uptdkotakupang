<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\Setting;
use Illuminate\Database\QueryException;

class ShowPublicServiceBoard
{
    /**
     * Build the public service board without the counters' internal IDs.
     * "diperbarui" is null until the board is first saved, so the portal can
     * tell an empty board from a stale one. A source that cannot be read
     * fails the request instead of showing an empty board.
     *
     * @return array{nomorAntrean: string, layananAntrean: string, waktuTunggu: int, selisihKemarin: int, loket: list<array{kode: string, nama: string, status: string}>, catatan: string, diperbarui: ?string}
     *
     * @throws PublicContentUnavailableException
     */
    public function handle(): array
    {
        try {
            $board = Setting::serviceBoard();
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The service board could not be read.', previous: $exception);
        }

        return [
            'nomorAntrean' => $board['nomorAntrean'],
            'layananAntrean' => $board['layananAntrean'],
            'waktuTunggu' => $board['waktuTunggu'],
            'selisihKemarin' => $board['selisihKemarin'],
            'loket' => array_map(fn (array $counter): array => [
                'kode' => $counter['kode'],
                'nama' => $counter['nama'],
                'status' => $counter['status'],
            ], $board['loket']),
            'catatan' => $board['catatan'],
            'diperbarui' => $board['diperbarui'],
        ];
    }
}
