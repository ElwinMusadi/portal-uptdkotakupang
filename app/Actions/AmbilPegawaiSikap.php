<?php

namespace App\Actions;

use App\Support\SikapClient;

/**
 * Read one active employee from SIKAP by NIP (D-075): when an administrator
 * picks them in Akun Pegawai, and again on the server when their account is
 * saved, so the account takes its name from SIKAP and not from the browser.
 * SIKAP records every such read in its activity log, so it is never asked
 * for each search result. The e-mail address and phone number in SIKAP's
 * answer are dropped and reach neither PINTU's database, log nor browser.
 */
class AmbilPegawaiSikap
{
    public function __construct(private SikapClient $sikap) {}

    /**
     * @return array{status: 'found', pegawai: array{nip: string, nama: string, jabatan: ?string, unit: ?string}}|array{status: 'not_found'}|array{status: 'unavailable', message: string}
     */
    public function handle(string $nip): array
    {
        if (preg_match('/^\d{18}\z/', $nip) !== 1) {
            return ['status' => 'not_found'];
        }

        $result = $this->sikap->get("/pegawai/{$nip}", notFoundIsAnswer: true);

        if ($result['status'] !== 'ok') {
            return $result;
        }

        $employee = $this->sikap->employee($result['body']);

        return $employee !== null && $employee['nip'] === $nip
            ? ['status' => 'found', 'pegawai' => $employee]
            : $this->sikap->invalidAnswer();
    }
}
