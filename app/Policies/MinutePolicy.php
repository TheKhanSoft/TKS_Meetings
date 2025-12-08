<?php

namespace App\Policies;

use App\Models\Minute;
use App\Models\User;

class MinutePolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view minutes');
    }

    public function view(User $user, Minute $minute): bool
    {
        return $user->can('view', $minute->agendaItem->meeting);
    }

    public function create(User $user): bool
    {
        return $user->can('create minutes');
    }

    public function update(User $user, Minute $minute): bool
    {
        return $user->can('update', $minute->agendaItem->meeting);
    }

    public function delete(User $user, Minute $minute): bool
    {
        return $user->can('update', $minute->agendaItem->meeting);
    }

    public function restore(User $user, Minute $minute): bool
    {
        return $user->can('update', $minute->agendaItem->meeting);
    }

    public function forceDelete(User $user, Minute $minute): bool
    {
        return $user->can('force_delete minutes') && $user->can('update', $minute->agendaItem->meeting);
    }

    public function export(User $user): bool
    {
        return $user->can('export minutes');
    }

    public function import(User $user): bool
    {
        return $user->can('import minutes');
    }
}
