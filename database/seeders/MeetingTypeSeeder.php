<?php

namespace Database\Seeders;

use App\Models\MeetingType;
use Illuminate\Database\Seeder;

class MeetingTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Academic Council',
                'code' => 'AC',
                'description' => 'University level body to approve academic policies and curricula.',
                'is_active' => true,
            ],
            [
                'name' => 'Affiliation Committee',
                'code' => 'Affiliation',
                'description' => 'Handles matters related to college affiliations.',
                'is_active' => true,
            ],
            [
                'name' => 'Advanced Studies and Research Board',
                'code' => 'ASRB',
                'description' => 'Oversees postgraduate studies and research activities.',
                'is_active' => true,
            ],
            [
                'name' => 'Extention Committee',
                'code' => 'EC',
                'description' => 'Handles extension cases and related matters of the students.',
                'is_active' => true,
            ],
            [
                'name' => 'Selection Board',
                'code' => 'SB',
                'description' => 'Meeting for the recruitment of faculty and senior staff.',
                'is_active' => true,
            ],
            [
                'name' => 'Syndicate',
                'code' => 'SYN',
                'description' => 'Executive body of the university.',
                'is_active' => true,
            ],
            [
                'name' => 'Board of Studies',
                'code' => 'BOS',
                'description' => 'Departmental level meeting to discuss curriculum and academic matters.',
                'is_active' => true,
            ],
            [
                'name' => 'Board of Faculty',
                'code' => 'BOF',
                'description' => 'Faculty level meeting to discuss academic and administrative issues.',
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            MeetingType::firstOrCreate(['code' => $type['code']], $type);
        }
    }
}
