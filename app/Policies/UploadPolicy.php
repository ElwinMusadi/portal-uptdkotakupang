<?php

namespace App\Policies;

use App\Enums\UploadTarget;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Uploads (D-028): managers of the target's module upload for it; every
 * employee uploads field attendance photos. A private file is read by the
 * employee who uploaded it and by administrators.
 */
class UploadPolicy
{
    /**
     * Determine whether the user can upload a file for the target.
     */
    public function create(User $user, UploadTarget $target): Response
    {
        $module = $target->module();

        return $module === null || in_array($module->value, $user->managedModules(), true)
            ? Response::allow()
            : Response::deny("Modul {$module->label()} belum ditugaskan kepada Anda.");
    }

    /**
     * Determine whether the user can read a private file.
     */
    public function view(User $user, Upload $upload): Response
    {
        return $upload->user_id === $user->id || $user->isAdministrator()
            ? Response::allow()
            : Response::deny('Berkas ini tidak dapat Anda buka.');
    }
}
