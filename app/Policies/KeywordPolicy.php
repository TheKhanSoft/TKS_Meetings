<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Keyword;

class KeywordPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view keywords');
    }

    public function view(User $user, Keyword $keyword): bool
    {
        return $user->can('view keywords');
    }

    public function create(User $user): bool
    {
        return $user->can('create keywords');
    }

    public function update(User $user, Keyword $keyword): bool
    {
        return $user->can('edit keywords');
    }

    public function delete(User $user, Keyword $keyword): bool
    {
        return $user->can('delete keywords');
    }

    public function restore(User $user, Keyword $keyword): bool
    {
        return $user->can('restore keywords');
    }

    public function forceDelete(User $user, Keyword $keyword): bool
    {
        return $user->can('force_delete keywords');
    }

    public function export(User $user): bool
    {
        return $user->can('export keywords');
    }

    public function import(User $user): bool
    {
        return $user->can('import keywords');
    }
}
