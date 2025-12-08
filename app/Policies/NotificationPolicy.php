<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Notification;

class NotificationPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view notifications');
    }

    public function view(User $user, Notification $notification): bool
    {
        return $user->can('view notifications');
    }

    public function create(User $user): bool
    {
        return $user->can('create notifications');
    }

    public function update(User $user, Notification $notification): bool
    {
        return $user->can('edit notifications');
    }

    public function delete(User $user, Notification $notification): bool
    {
        return $user->can('delete notifications');
    }

    public function restore(User $user, Notification $notification): bool
    {
        return $user->can('restore notifications');
    }

    public function forceDelete(User $user, Notification $notification): bool
    {
        return $user->can('force_delete notifications');
    }

    public function export(User $user): bool
    {
        return $user->can('export notifications');
    }

    public function import(User $user): bool
    {
        return $user->can('import notifications');
    }
}
