<?php

namespace App\Actions;

use App\Enums\DashboardModule;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeleteEntries
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * Delete the listed entries of a dashboard collection that still exist,
     * logged in the same transaction: by the entry's title when it is one,
     * otherwise as "N entri". Nothing is logged when nothing was deleted.
     *
     * @param  class-string<Model>  $model
     * @param  list<string>  $ids
     * @param  Closure(Model): string  $title
     * @return int The number of entries deleted.
     */
    public function handle(User $user, DashboardModule $module, string $model, array $ids, Closure $title): int
    {
        return DB::transaction(function () use ($user, $module, $model, $ids, $title): int {
            $entries = $model::query()->whereKey($ids)->get();

            if ($entries->isEmpty()) {
                return 0;
            }

            $model::query()->whereKey($entries->modelKeys())->delete();

            $target = $entries->count() === 1 ? $title($entries->first()) : $entries->count().' entri';
            $this->recordActivity->handle($user, $module, 'menghapus', $target);

            return $entries->count();
        });
    }
}
