<?php

namespace App\Policies;

use App\Models\User;
use App\Models\HelpCategory;

class HelpCategoryPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view help categories');
    }

    public function view(User $user, HelpCategory $helpCategory): bool
    {
        return $user->can('view help categories');
    }

    public function create(User $user): bool
    {
        return $user->can('create help categories');
    }

    public function update(User $user, HelpCategory $helpCategory): bool
    {
        return $user->can('edit help categories');
    }

    public function delete(User $user, HelpCategory $helpCategory): bool
    {
        return $user->can('delete help categories');
    }

    public function restore(User $user, HelpCategory $helpCategory): bool
    {
        return $user->can('restore help categories');
    }

    public function forceDelete(User $user, HelpCategory $helpCategory): bool
    {
        return $user->can('force_delete help categories');
    }

    public function export(User $user): bool
    {
        return $user->can('export help categories');
    }

    public function import(User $user): bool
    {
        return $user->can('import help categories');
    }
}
