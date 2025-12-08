<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Announcement;

class AnnouncementPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view announcements');
    }

    public function view(User $user, Announcement $announcement): bool
    {
        return $user->can('view announcements');
    }

    public function create(User $user): bool
    {
        return $user->can('create announcements');
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->can('edit announcements');
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->can('delete announcements');
    }

    public function restore(User $user, Announcement $announcement): bool
    {
        return $user->can('restore announcements');
    }

    public function forceDelete(User $user, Announcement $announcement): bool
    {
        return $user->can('force_delete announcements');
    }

    public function export(User $user): bool
    {
        return $user->can('export announcements');
    }

    public function import(User $user): bool
    {
        return $user->can('import announcements');
    }
}
