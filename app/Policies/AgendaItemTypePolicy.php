<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AgendaItemType;

class AgendaItemTypePolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view agenda item types');
    }

    public function view(User $user, AgendaItemType $agendaItemType): bool
    {
        return $user->can('view agenda item types');
    }

    public function create(User $user): bool
    {
        return $user->can('create agenda item types');
    }

    public function update(User $user, AgendaItemType $agendaItemType): bool
    {
        return $user->can('edit agenda item types');
    }

    public function delete(User $user, AgendaItemType $agendaItemType): bool
    {
        return $user->can('delete agenda item types');
    }

    public function restore(User $user, AgendaItemType $agendaItemType): bool
    {
        return $user->can('restore agenda item types');
    }

    public function forceDelete(User $user, AgendaItemType $agendaItemType): bool
    {
        return $user->can('force_delete agenda item types');
    }

    public function export(User $user): bool
    {
        return $user->can('export agenda item types');
    }

    public function import(User $user): bool
    {
        return $user->can('import agenda item types');
    }
}
