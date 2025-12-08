<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view users');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('view users');
    }

    public function create(User $user): bool
    {
        return $user->can('create users');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('edit users');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('delete users');
    }

    public function restore(User $user, User $model): bool
    {
        return $user->can('restore users');
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->can('force_delete users');
    }

    public function export(User $user): bool
    {
        return $user->can('export users');
    }

    public function import(User $user): bool
    {
        return $user->can('import users');
    }

    public function assignPositions(User $user): bool
    {
        return $user->can('assign positions');
    }

    public function assignPermissions(User $user): bool
    {
        return $user->can('assign permissions');
    }

    public function assignRoles(User $user): bool
    {
        return $user->can('assign roles');
    }
}
