<?php

namespace App\Actions;

use App\Support\SikapClient;

/**
 * Search SIKAP's active employees by part of a name or NIP for the employee
 * picker of Akun Pegawai (D-075). SIKAP's search never includes contact
 * details, and only what the picker shows is kept: NIP, name, position and
 * unit. Searches are not logged.
 */
class CariPegawaiSikap
{
    /**
     * Employees asked of SIKAP per search; a longer list is narrowed by typing more.
     */
    public const int PAGE_SIZE = 20;

    public function __construct(private SikapClient $sikap) {}

    /**
     * The first employees matching the search, by name, and how many match in all.
     *
     * @return array{status: 'found', pegawai: list<array{nip: string, nama: string, jabatan: ?string, unit: ?string}>, total: int}|array{status: 'unavailable', message: string}
     */
    public function handle(string $search): array
    {
        $result = $this->sikap->get('/pegawai', ['q' => $search, 'pageSize' => self::PAGE_SIZE]);

        if ($result['status'] !== 'ok') {
            return $result;
        }

        $items = $result['body']['items'] ?? null;
        if (! is_array($items) || ! array_is_list($items)) {
            return $this->sikap->invalidAnswer();
        }

        $employees = array_values(array_filter(array_map($this->sikap->employee(...), $items)));
        $total = $result['body']['total'] ?? null;

        return [
            'status' => 'found',
            'pegawai' => $employees,
            'total' => is_int($total) ? max($total, count($employees)) : count($employees),
        ];
    }
}
