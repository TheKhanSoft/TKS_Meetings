<?php

namespace Database\Seeders;

use App\Models\AgendaItemType;
use Illuminate\Database\Seeder;

class AgendaItemTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Regular Agenda Item', 'is_active' => true],
            ['name' => 'Additional Agenda Item', 'is_active' => true],
            ['name' => 'Table Agenda Item', 'is_active' => true],
            ['name' => 'Special Agenda Item', 'is_active' => true],
            ['name' => 'Ratification Item', 'is_active' => true],
            ['name' => 'Any Other Business', 'is_active' => true],
        ];

        foreach ($types as $type) {
            AgendaItemType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
