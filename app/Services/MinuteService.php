<?php

namespace App\Services;

use App\Models\Minute;

class MinuteService
{
    public function getAllMinutes()
    {
        return Minute::with(['agendaItem', 'responsibleUser'])->latest()->get();
    }

    public function createMinute(array $data): Minute
    {
        return Minute::create($data);
    }

    public function updateMinute(Minute $minute, array $data): Minute
    {
        $minute->update($data);
        return $minute;
    }

    public function deleteMinute(Minute $minute): bool
    {
        return $minute->delete();
    }
}
