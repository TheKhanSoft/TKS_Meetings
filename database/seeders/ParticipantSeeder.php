<?php

namespace Database\Seeders;

use App\Models\Meeting;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Database\Seeder;

class ParticipantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create a pool of External Participants
        $externalParticipants = Participant::factory()->count(50)->create();
        $internalUsers = User::all();
        $meetings = Meeting::all();

        foreach ($meetings as $meeting) {
            // --- MEMBERS (For Agenda) ---
            // Add 3-5 Members
            $members = $externalParticipants->random(rand(3, 5));
            foreach ($members as $participant) {
                $meeting->participants()->attach($participant->id, ['type' => 'member']);
            }

            // --- ATTENDEES (For Minutes) ---
            // Add 2-4 Attendees
            $attendees = $externalParticipants->random(rand(2, 4));
            foreach ($attendees as $participant) {
                // Check if already attached as member? The unique constraint is on (meeting_id, participable_id, participable_type, type).
                // So same person can be member AND attendee.
                $meeting->participants()->attach($participant->id, ['type' => 'attendee']);
            }
        }
    }
}
