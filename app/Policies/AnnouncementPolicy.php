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
        // 1. Global permission check
        if (! $user->can('view announcements')) {
            return false;
        }

        // 2. Super View check
        if ($user->can('view hidden announcements')) {
            return true;
        }

        // 3. Creator check
        if ($announcement->created_by === $user->id) {
            return true;
        }

        // 4. Visibility Rules
        
        // Active
        if (! $announcement->is_active) {
            return false;
        }

        // Scheduled
        if ($announcement->published_at && $announcement->published_at->isFuture()) {
            if (! $user->can('view scheduled announcements')) {
                return false;
            }
        }

        // Expired
        if ($announcement->expires_at && $announcement->expires_at->isPast()) {
            return false;
        }

        // Audience - Exceptions
        if ($announcement->excludedUsers()->where('user_id', $user->id)->exists()) {
            return false;
        }

        // Audience - Target
        if ($announcement->audience_type === 'users') {
            if (! $announcement->targetUsers()->where('user_id', $user->id)->exists()) {
                return false;
            }
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('create announcements');
    }

    public function update(User $user, Announcement $announcement): bool
    {
        if ($user->can('edit all announcements')) {
            return true;
        }

        return $user->can('edit announcements') && $user->id === $announcement->created_by;
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        if ($user->can('delete all announcements')) {
            return true;
        }

        return $user->can('delete announcements') && $user->id === $announcement->created_by;
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
