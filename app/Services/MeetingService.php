<?php

namespace App\Services;

use App\Models\Meeting;
use Illuminate\Support\Facades\Auth;

class MeetingService
{
    public function getAllMeetings()
    {
        return Meeting::with(['meetingType', 'director', 'registrar', 'vc'])->latest()->get();
    }

    public function createMeeting(array $data, array $participants = []): Meeting
    {
        $data['entry_by_id'] = Auth::id();
        $meeting = Meeting::create($data);

        if (!empty($participants)) {
            $this->syncParticipants($meeting, $participants);
        }

        return $meeting;
    }

    public function updateMeeting(Meeting $meeting, array $data, array $participants = []): Meeting
    {
        $meeting->update($data);

        if (!empty($participants)) {
            $this->syncParticipants($meeting, $participants);
        }

        return $meeting;
    }

    protected function syncParticipants(Meeting $meeting, array $participants)
    {
        $syncData = [];
        
        if (isset($participants['members'])) {
            foreach ($participants['members'] as $id) {
                $syncData[$id] = ['type' => 'member'];
            }
        }
        
        if (isset($participants['attendees'])) {
            foreach ($participants['attendees'] as $id) {
                $syncData[$id] = ['type' => 'attendee'];
            }
        }
        
        $meeting->participants()->sync($syncData);
    }

    public function deleteMeeting(Meeting $meeting): bool
    {
        return $meeting->delete();
    }
}
