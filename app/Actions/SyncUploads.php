<?php

namespace App\Actions;

use App\Enums\UploadTarget;
use App\Models\Upload;

class SyncUploads
{
    /**
     * Keep the files saved content uses and remove the ones it no longer
     * uses (D-028): uploads referenced afterwards are marked as used, and
     * uploads referenced before but not afterwards are deleted with their
     * files once the change is committed. Addresses that are not uploads
     * for the target are ignored.
     *
     * @param  list<?string>  $before  The file addresses the content used before.
     * @param  list<?string>  $after  The file addresses it uses now.
     */
    public function handle(UploadTarget $target, array $before, array $after): void
    {
        $kept = [];

        foreach (array_filter($after) as $url) {
            $upload = Upload::fromUrl($url, $target);

            if ($upload !== null) {
                $upload->markUsed();
                $kept[] = $upload->id;
            }
        }

        foreach (array_filter($before) as $url) {
            $upload = Upload::fromUrl($url, $target);

            if ($upload !== null && ! in_array($upload->id, $kept, true)) {
                $upload->delete();
            }
        }
    }
}
