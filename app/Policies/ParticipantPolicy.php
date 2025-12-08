<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Participant;

class ParticipantPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view participants');
    }

    public function view(User $user, Participant $participant): bool
    {
        return $user->can('view participants');
    }

    public function create(User $user): bool
    {
        return $user->can('create participants');
    }

    public function update(User $user, Participant $participant): bool
    {
        return $user->can('edit participants');
    }

    public function delete(User $user, Participant $participant): bool
    {
        return $user->can('delete participants');
    }

    public function restore(User $user, Participant $participant): bool
    {
        return $user->can('restore participants');
    }

    public function forceDelete(User $user, Participant $participant): bool
    {
        return $user->can('force_delete participants');
    }

    public function export(User $user): bool
    {
        return $user->can('export participants');
    }

    public function import(User $user): bool
    {
        return $user->can('import participants');
    }
}
