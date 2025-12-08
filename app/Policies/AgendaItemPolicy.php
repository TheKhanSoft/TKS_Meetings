<?php

namespace App\Policies;

use App\Models\AgendaItem;
use App\Models\User;

class AgendaItemPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view agenda items');
    }

    public function view(User $user, AgendaItem $agendaItem): bool
    {
        // If user can view the meeting, they can view the agenda item
        return $user->can('view', $agendaItem->meeting);
    }

    public function create(User $user): bool
    {
        // Global check. Specific meeting check should be done in controller/livewire
        return $user->can('create agenda items');
    }

    public function update(User $user, AgendaItem $agendaItem): bool
    {
        // If user can edit the meeting, they can edit its agenda items
        return $user->can('update', $agendaItem->meeting);
    }

    public function delete(User $user, AgendaItem $agendaItem): bool
    {
        // If user can edit the meeting, they can delete its agenda items
        return $user->can('update', $agendaItem->meeting);
    }

    public function restore(User $user, AgendaItem $agendaItem): bool
    {
        return $user->can('update', $agendaItem->meeting);
    }

    public function forceDelete(User $user, AgendaItem $agendaItem): bool
    {
        return $user->can('force_delete agenda items') && $user->can('update', $agendaItem->meeting);
    }

    public function export(User $user): bool
    {
        return $user->can('export agenda items');
    }

    public function import(User $user): bool
    {
        return $user->can('import agenda items');
    }
}
