<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view roles');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('view roles');
    }

    public function create(User $user): bool
    {
        return $user->can('create roles');
    }

    public function update(User $user, Role $role): bool
    {
        if ($role->name === 'Super Admin' && !$user->hasRole('Super Admin')) {
            return false;
        }
        return $user->can('edit roles');
    }

    public function delete(User $user, Role $role): bool
    {
        if ($role->name === 'Super Admin') {
            return false;
        }
        return $user->can('delete roles');
    }

    public function restore(User $user, Role $role): bool
    {
        return $user->can('restore roles');
    }

    public function forceDelete(User $user, Role $role): bool
    {
        return $user->can('force_delete roles');
    }

    public function export(User $user): bool
    {
        return $user->can('export roles');
    }

    public function import(User $user): bool
    {
        return $user->can('import roles');
    }
}
