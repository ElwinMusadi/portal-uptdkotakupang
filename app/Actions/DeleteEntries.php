<?php

namespace App\Actions;

use App\Enums\DashboardModule;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeleteEntries
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * Delete the listed entries of a dashboard collection that still exist,
     * logged in the same transaction: by the entry's title when it is one,
     * otherwise as "N entri". Nothing is logged when nothing was deleted.
     * The entries are deleted without model events; $afterDelete runs in the
     * same transaction with the deleted entries, e.g. to remove their files.
     *
     * @param  class-string<Model>  $model
     * @param  list<string>  $ids
     * @param  Closure(Model): string  $title
     * @param  ?Closure(Collection<int, Model>): void  $afterDelete
     * @return int The number of entries deleted.
     */
    public function handle(User $user, DashboardModule $module, string $model, array $ids, Closure $title, ?Closure $afterDelete = null): int
    {
        return DB::transaction(function () use ($user, $module, $model, $ids, $title, $afterDelete): int {
            $entries = $model::query()->whereKey($ids)->get();

            if ($entries->isEmpty()) {
                return 0;
            }

            $model::query()->whereKey($entries->modelKeys())->delete();

            if ($afterDelete !== null) {
                $afterDelete($entries);
            }

            $target = $entries->count() === 1 ? $title($entries->first()) : $entries->count().' entri';
            $this->recordActivity->handle($user, $module, 'menghapus', $target);

            return $entries->count();
        });
    }
}
