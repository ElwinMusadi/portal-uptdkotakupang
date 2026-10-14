<?php

namespace App\Policies;

use App\Enums\DashboardModule;
use App\Models\Article;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Articles belong to the Berita & Pengumuman module (D-010). Announcements
 * are often urgent, so its managers take every workflow step themselves,
 * including approving, publishing and withdrawing, and change published
 * articles (D-031). Only administrators delete.
 */
class ArticlePolicy
{
    private const string MODULE_ONLY = 'Modul Berita & Pengumuman belum ditugaskan kepada Anda.';

    /**
     * Determine whether the user can list the articles. Managers of the
     * update schedule (Jadwal Pemutakhiran) may too, to see when the news
     * last changed.
     */
    public function viewAny(User $user): Response
    {
        return in_array(DashboardModule::Pemutakhiran->value, $user->managedModules(), true)
            ? Response::allow()
            : $this->managesNews($user);
    }

    /**
     * Determine whether the user can read an article.
     */
    public function view(User $user, Article $article): Response
    {
        return $this->managesNews($user);
    }

    /**
     * Determine whether the user can add articles, which start as drafts.
     */
    public function create(User $user): Response
    {
        return $this->managesNews($user);
    }

    /**
     * Determine whether the user can change an article or take a workflow
     * step from its status.
     */
    public function update(User $user, Article $article): Response
    {
        return $this->managesNews($user);
    }

    /**
     * Determine whether the user can delete articles.
     */
    public function deleteAny(User $user): Response
    {
        return $user->isAdministrator()
            ? Response::allow()
            : Response::deny('Hanya Administrator yang dapat menghapus tulisan.');
    }

    private function managesNews(User $user): Response
    {
        return in_array(DashboardModule::Berita->value, $user->managedModules(), true)
            ? Response::allow()
            : Response::deny(self::MODULE_ONLY);
    }
}
