<?php

namespace App\Models;

use App\Enums\UploadTarget;
use Database\Factories\UploadFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * A file uploaded from the dashboard (D-028). Public files are in the
 * public bucket (the "public" disk) under a name nobody can guess; private
 * ones are in the private bucket (the default disk) and only reach the
 * browser through the application. A file that no saved content uses is
 * removed after a day, and removing the record removes the file.
 */
class Upload extends Model
{
    /** @use HasFactory<UploadFactory> */
    use HasFactory, HasUlids, Prunable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tujuan' => UploadTarget::class,
            'publik' => 'boolean',
            'ukuran' => 'integer',
            'dipakai_pada' => 'datetime',
        ];
    }

    /**
     * Remove the stored file with its record, once the deletion is committed.
     */
    protected static function booted(): void
    {
        static::deleted(function (Upload $upload): void {
            DB::afterCommit(fn () => $upload->disk()->delete($upload->path));
        });
    }

    /**
     * The employee who uploaded the file.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The disk holding the file: the public bucket, or the private default.
     */
    public function disk(): Filesystem
    {
        return $this->publik ? Storage::disk('public') : Storage::disk();
    }

    /**
     * Where the browser reads the file: the public bucket's address, or the
     * application's authorized route for private files.
     */
    public function url(): string
    {
        return $this->publik
            ? $this->disk()->url($this->path)
            : route('berkas.show', $this);
    }

    /**
     * The upload a saved URL points to, provided it was uploaded for the
     * given target and the URL is exactly its own.
     */
    public static function fromUrl(string $url, UploadTarget $target): ?self
    {
        if (preg_match('/([0-9a-hjkmnp-tv-z]{26})(?:\.[a-z0-9]+)?$/i', $url, $match) !== 1) {
            return null;
        }

        $upload = self::query()->find(strtolower($match[1]));

        return $upload !== null && $upload->tujuan === $target && $upload->url() === $url ? $upload : null;
    }

    /**
     * Mark the file as used by saved content, so it is not pruned.
     */
    public function markUsed(): void
    {
        if ($this->dipakai_pada === null) {
            $this->forceFill(['dipakai_pada' => now()])->save();
        }
    }

    /**
     * Files no saved content has used within a day of their upload.
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return static::query()->whereNull('dipakai_pada')->where('created_at', '<', now()->subDay());
    }
}
