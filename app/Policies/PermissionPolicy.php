<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view permissions');
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->can('view permissions');
    }

    public function create(User $user): bool
    {
        return $user->can('create permissions');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->can('edit permissions');
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->can('delete permissions');
    }

    public function restore(User $user, Permission $permission): bool
    {
        return $user->can('restore permissions');
    }

    public function forceDelete(User $user, Permission $permission): bool
    {
        return $user->can('force_delete permissions');
    }

    public function export(User $user): bool
    {
        return $user->can('export permissions');
    }

    public function import(User $user): bool
    {
        return $user->can('import permissions');
    }
}
