<?php

namespace App\Enums;

/**
 * The publication workflow of reviewed content (collection, review,
 * approval, publication), with the dashboard's status names. Only published
 * entries reach the portal. On the server, approving, returning from
 * approval and withdrawing are for administrators (D-022).
 */
enum WorkflowStatus: string
{
    case Draft = 'draf';
    case UnderReview = 'diperiksa';
    case AwaitingApproval = 'persetujuan';
    case Published = 'terbit';
    case Returned = 'dikembalikan';
    case Archived = 'arsip';

    /**
     * The status as the dashboard names it.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::UnderReview => 'Diperiksa',
            self::AwaitingApproval => 'Menunggu persetujuan',
            self::Published => 'Terbit',
            self::Returned => 'Dikembalikan',
            self::Archived => 'Arsip',
        };
    }

    /**
     * The steps from this status, keyed by the status they lead to, with the
     * verb the activity log and the history use (the dashboard's AKSI_ALUR).
     *
     * @return array<string, string>
     */
    public function steps(): array
    {
        return match ($this) {
            self::Draft => [self::UnderReview->value => 'mengajukan pemeriksaan'],
            self::UnderReview => [self::AwaitingApproval->value => 'meneruskan ke penyetuju', self::Returned->value => 'mengembalikan'],
            self::AwaitingApproval => [self::Published->value => 'menyetujui dan menerbitkan', self::Returned->value => 'mengembalikan'],
            self::Published => [self::Archived->value => 'menarik dari portal'],
            self::Returned => [self::UnderReview->value => 'mengajukan ulang'],
            self::Archived => [self::Draft->value => 'memulihkan ke draf'],
        };
    }

    /**
     * Whether the workflow allows a step from this status to the given one.
     */
    public function canMoveTo(self $status): bool
    {
        return array_key_exists($status->value, $this->steps());
    }

    /**
     * Whether entries in this status are awaiting approval or public, so only
     * administrators may change them: their content and every step from them
     * (approving, returning from approval, withdrawing).
     */
    public function isLocked(): bool
    {
        return $this === self::AwaitingApproval || $this === self::Published;
    }
}
