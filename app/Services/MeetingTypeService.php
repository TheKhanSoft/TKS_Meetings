<?php

namespace App\Services;

use App\Models\MeetingType;

class MeetingTypeService
{
    public function getAllMeetingTypes()
    {
        return MeetingType::latest()->get();
    }

    public function createMeetingType(array $data): MeetingType
    {
        return MeetingType::create($data);
    }

    public function updateMeetingType(MeetingType $meetingType, array $data): MeetingType
    {
        $meetingType->update($data);
        return $meetingType;
    }

    public function deleteMeetingType(MeetingType $meetingType): bool
    {
        return $meetingType->delete();
    }
}
