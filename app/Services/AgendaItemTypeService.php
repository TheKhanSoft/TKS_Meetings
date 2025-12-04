<?php

namespace App\Services;

use App\Models\AgendaItemType;

class AgendaItemTypeService
{
    public function getAllAgendaItemTypes()
    {
        return AgendaItemType::latest()->get();
    }

    public function createAgendaItemType(array $data): AgendaItemType
    {
        return AgendaItemType::create($data);
    }

    public function updateAgendaItemType(AgendaItemType $agendaItemType, array $data): AgendaItemType
    {
        $agendaItemType->update($data);
        return $agendaItemType;
    }

    public function deleteAgendaItemType(AgendaItemType $agendaItemType): bool
    {
        return $agendaItemType->delete();
    }
}
