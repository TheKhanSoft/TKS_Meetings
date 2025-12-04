<?php

namespace Database\Seeders;

use App\Models\Meeting;
use App\Models\MeetingType;
use App\Models\User;
use Illuminate\Database\Seeder;

class MeetingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vc = User::role('VC')->first();
        $registrar = User::role('Registrar')->first();
        $director = User::role('Director')->first();
        $staff = User::role('Staff')->first();

        $acType = MeetingType::where('code', 'AC')->first();
        $bosType = MeetingType::where('code', 'BOS')->first();

        if (!$vc || !$registrar || !$director || !$staff || !$acType || !$bosType) {
            return;
        }

        $meetings = [
            [
                'title' => '34th Meeting of Academic Council',
                'number' => 'AC-34',
                'meeting_type_id' => $acType->id,
                'date' => '2025-10-15',
                'time' => '10:00:00',
                'is_last' => true,
                'director_id' => $director->id,
                'registrar_id' => $registrar->id,
                'vc_id' => $vc->id,
                'entry_by_id' => $staff->id,
            ],
            [
                'title' => '12th Meeting of Board of Studies - CS Dept',
                'number' => 'BOS-CS-12',
                'meeting_type_id' => $bosType->id,
                'date' => '2025-09-20',
                'time' => '11:30:00',
                'is_last' => true,
                'director_id' => $director->id,
                'registrar_id' => $registrar->id,
                'vc_id' => $vc->id,
                'entry_by_id' => $staff->id,
            ],
            [
                'title' => '33rd Meeting of Academic Council',
                'number' => 'AC-33',
                'meeting_type_id' => $acType->id,
                'date' => '2025-06-10',
                'time' => '10:00:00',
                'is_last' => false,
                'director_id' => $director->id,
                'registrar_id' => $registrar->id,
                'vc_id' => $vc->id,
                'entry_by_id' => $staff->id,
            ],
        ];

        foreach ($meetings as $meeting) {
            Meeting::firstOrCreate(['number' => $meeting['number']], $meeting);
        }
    }
}
