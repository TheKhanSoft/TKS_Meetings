<?php

namespace Database\Seeders;

use App\Models\AgendaItem;
use App\Models\AgendaItemType;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Seeder;

class AgendaItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $meetingAC34 = Meeting::where('number', 'AC-34')->first();
        $meetingBOS12 = Meeting::where('number', 'BOS-CS-12')->first();
        
        $regularType = AgendaItemType::where('name', 'Regular Agenda Item')->first();
        $confirmationType = AgendaItemType::where('name', 'Confirmation of Minutes')->first();
        
        $faculty = User::role('Faculty')->first();
        $director = User::role('Director')->first();

        if (!$meetingAC34 || !$meetingBOS12 || !$regularType || !$confirmationType) {
            return;
        }

        $items = [
            // AC-34 Items
            [
                'meeting_id' => $meetingAC34->id,
                'agenda_item_type_id' => $confirmationType->id,
                'sequence_number' => 1,
                'title' => 'Confirmation of Minutes of 33rd Academic Council Meeting',
                'details' => 'To confirm the minutes of the previous meeting held on 2025-06-10.',
                'owner_user_id' => $director->id,
                'discussion_status' => 'discussed',
                'is_left_over' => false,
            ],
            [
                'meeting_id' => $meetingAC34->id,
                'agenda_item_type_id' => $regularType->id,
                'sequence_number' => 2,
                'title' => 'Approval of Fall 2025 Curriculum for BS Computer Science',
                'details' => 'The Board of Studies has recommended the revised curriculum for approval.',
                'owner_user_id' => $faculty->id,
                'discussion_status' => 'discussed',
                'is_left_over' => false,
            ],
            [
                'meeting_id' => $meetingAC34->id,
                'agenda_item_type_id' => $regularType->id,
                'sequence_number' => 3,
                'title' => 'Policy on AI Usage in Assignments',
                'details' => 'Discussion on formulating a policy regarding the use of Generative AI by students.',
                'owner_user_id' => $director->id,
                'discussion_status' => 'deferred',
                'is_left_over' => true,
            ],

            // BOS-CS-12 Items
            [
                'meeting_id' => $meetingBOS12->id,
                'agenda_item_type_id' => $regularType->id,
                'sequence_number' => 1,
                'title' => 'Revision of Course Codes',
                'details' => 'Proposal to align course codes with HEC new policy.',
                'owner_user_id' => $faculty->id,
                'discussion_status' => 'discussed',
                'is_left_over' => false,
            ],
        ];

        foreach ($items as $item) {
            AgendaItem::firstOrCreate(
                ['meeting_id' => $item['meeting_id'], 'sequence_number' => $item['sequence_number']],
                $item
            );
        }
    }
}
