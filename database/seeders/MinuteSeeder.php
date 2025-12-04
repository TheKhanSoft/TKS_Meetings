<?php

namespace Database\Seeders;

use App\Models\AgendaItem;
use App\Models\Minute;
use App\Models\User;
use Illuminate\Database\Seeder;

class MinuteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $item1 = AgendaItem::where('title', 'Confirmation of Minutes of 33rd Academic Council Meeting')->first();
        $item2 = AgendaItem::where('title', 'Approval of Fall 2025 Curriculum for BS Computer Science')->first();
        $item3 = AgendaItem::where('title', 'Revision of Course Codes')->first();

        $registrar = User::role('Registrar')->first();
        $director = User::role('Director')->first();

        if (!$item1 || !$item2 || !$item3) {
            return;
        }

        $minutes = [
            [
                'agenda_item_id' => $item1->id,
                'decision' => 'The minutes of the 33rd Academic Council meeting were confirmed without any changes.',
                'action_required' => 'None',
                'approval_status' => 'approved',
                'responsible_user_id' => $registrar->id,
                'target_due_date' => null,
            ],
            [
                'agenda_item_id' => $item2->id,
                'decision' => 'The council approved the revised curriculum for BS Computer Science effective from Fall 2025.',
                'action_required' => 'Notify all departments and update the website.',
                'approval_status' => 'approved',
                'responsible_user_id' => $director->id,
                'target_due_date' => '2025-11-01',
            ],
            [
                'agenda_item_id' => $item3->id,
                'decision' => 'The Board recommended the new course codes for approval by the Academic Council.',
                'action_required' => 'Submit to Academic Council as an agenda item.',
                'approval_status' => 'draft',
                'responsible_user_id' => $director->id,
                'target_due_date' => '2025-10-10',
            ],
        ];

        foreach ($minutes as $minute) {
            Minute::firstOrCreate(['agenda_item_id' => $minute['agenda_item_id']], $minute);
        }
    }
}
