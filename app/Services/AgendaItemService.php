<?php

namespace App\Services;

use App\Models\AgendaItem;

class AgendaItemService
{
    public function getAllAgendaItems()
    {
        return AgendaItem::with(['meeting', 'agendaItemType', 'owner'])->latest()->get();
    }

    public function createAgendaItem(array $data): AgendaItem
    {
        return AgendaItem::create($data);
    }

    public function updateAgendaItem(AgendaItem $agendaItem, array $data): AgendaItem
    {
        $agendaItem->update($data);
        return $agendaItem;
    }

    public function deleteAgendaItem(AgendaItem $agendaItem): bool
    {
        return $agendaItem->delete();
    }
}
