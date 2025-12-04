<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            SettingSeeder::class,
            HelpSeeder::class,
            KeywordSeeder::class,
            UserSeeder::class,
            MeetingTypeSeeder::class,
            AgendaItemTypeSeeder::class,
            // MeetingSeeder::class,
            // AgendaItemSeeder::class,
            // MinuteSeeder::class,
            // NotificationSeeder::class,
            ParticipantSeeder::class,
        ]);
    }
}
