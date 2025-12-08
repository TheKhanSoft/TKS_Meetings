<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Position;

class PositionPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view positions');
    }

    public function view(User $user, Position $position): bool
    {
        return $user->can('view positions');
    }

    public function create(User $user): bool
    {
        return $user->can('create positions');
    }

    public function update(User $user, Position $position): bool
    {
        return $user->can('edit positions');
    }

    public function delete(User $user, Position $position): bool
    {
        return $user->can('delete positions');
    }

    public function restore(User $user, Position $position): bool
    {
        return $user->can('restore positions');
    }

    public function forceDelete(User $user, Position $position): bool
    {
        return $user->can('force_delete positions');
    }

    public function export(User $user): bool
    {
        return $user->can('export positions');
    }

    public function import(User $user): bool
    {
        return $user->can('import positions');
    }
}
