<?php

namespace App\Policies;

use App\Models\MeetingType;
use App\Models\User;

class MeetingTypePolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view meeting types');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MeetingType $meetingType): bool
    {
        return $user->can('view meeting types');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create meeting types');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MeetingType $meetingType): bool
    {
        return $user->can('edit meeting types');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MeetingType $meetingType): bool
    {
        return $user->can('delete meeting types');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MeetingType $meetingType): bool
    {
        return $user->can('restore meeting types');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MeetingType $meetingType): bool
    {
        return $user->can('force_delete meeting types');
    }

    public function export(User $user): bool
    {
        return $user->can('export meeting types');
    }

    public function import(User $user): bool
    {
        return $user->can('import meeting types');
    }

    public function managePermissions(User $user, MeetingType $meetingType): bool
    {
        return $user->can('manage meeting permissions');
    }

    public function toggle(User $user, MeetingType $meetingType): bool
    {
        return $user->can('toggle meeting types');
    }
}
