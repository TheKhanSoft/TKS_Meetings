<?php

namespace App\Policies;

use App\Models\User;
use App\Models\EmploymentStatus;

class EmploymentStatusPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view employment statuses');
    }

    public function view(User $user, EmploymentStatus $employmentStatus): bool
    {
        return $user->can('view employment statuses');
    }

    public function create(User $user): bool
    {
        return $user->can('create employment statuses');
    }

    public function update(User $user, EmploymentStatus $employmentStatus): bool
    {
        return $user->can('edit employment statuses');
    }

    public function delete(User $user, EmploymentStatus $employmentStatus): bool
    {
        return $user->can('delete employment statuses');
    }

    public function restore(User $user, EmploymentStatus $employmentStatus): bool
    {
        return $user->can('restore employment statuses');
    }

    public function forceDelete(User $user, EmploymentStatus $employmentStatus): bool
    {
        return $user->can('force_delete employment statuses');
    }

    public function export(User $user): bool
    {
        return $user->can('export employment statuses');
    }

    public function import(User $user): bool
    {
        return $user->can('import employment statuses');
    }
}
