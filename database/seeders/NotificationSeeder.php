<?php

namespace Database\Seeders;

use App\Models\Minute;
use App\Models\Notification;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find a minute that was approved
        $minute = Minute::where('approval_status', 'approved')
                        ->where('decision', 'like', '%curriculum%')
                        ->first();

        if (!$minute) {
            return;
        }

        Notification::firstOrCreate(
            ['notification_no' => 'Notif-AC-34-02'],
            [
                'minute_id' => $minute->id,
                'notification_date' => '2025-10-20',
            ]
        );
    }
}
