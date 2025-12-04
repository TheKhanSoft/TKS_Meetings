<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // System Identity
            ['key' => 'site_name', 'value' => 'TKS Meetings', 'group' => 'system'],
            ['key' => 'site_description', 'value' => 'A comprehensive system for managing academic meetings, minutes, and agendas.', 'group' => 'system'],
            ['key' => 'organization_name', 'value' => 'TheKhanSoft', 'group' => 'system'],
            
            // Contact & Support
            ['key' => 'contact_email', 'value' => 'kashif.ahmad@awkum.edu.pk', 'group' => 'system'],
            ['key' => 'contact_phone', 'value' => '+92 314 9955914', 'group' => 'system'],
            ['key' => 'help_url', 'value' => '', 'group' => 'system'],
            
            // Localization & Formatting
            ['key' => 'timezone', 'value' => 'Asia/Karachi', 'group' => 'system'],
            ['key' => 'date_format', 'value' => 'Y-m-d', 'group' => 'system'],
            ['key' => 'time_format', 'value' => 'H:i', 'group' => 'system'],
            ['key' => 'default_locale', 'value' => 'en', 'group' => 'system'],
            
            // UI/UX
            ['key' => 'records_per_page', 'value' => '10', 'group' => 'system'],
            ['key' => 'pagination_size', 'value' => '15', 'group' => 'system'],
            ['key' => 'primary_color', 'value' => '#4f46e5', 'group' => 'system'],
            ['key' => 'logo_url', 'value' => '', 'group' => 'system'],
            ['key' => 'default_theme', 'value' => 'system', 'group' => 'system'],
            ['key' => 'enable_theme_toggle', 'value' => '1', 'group' => 'system'],
            ['key' => 'footer_text', 'value' => '© ' . date('Y') . ' TheKhanSoft. All rights reserved.', 'group' => 'system'],
            
            // Features
            // ['key' => 'enable_notifications', 'value' => '1', 'group' => 'features'],
            ['key' => 'enable_public_access', 'value' => '0', 'group' => 'features'],
            ['key' => 'enable_announcements', 'value' => '1', 'group' => 'features'],
            ['key' => 'maintenance_mode', 'value' => '0', 'group' => 'system'],
            ['key' => 'allow_registration', 'value' => '0', 'group' => 'system'],
            
            // Security
            ['key' => 'require_2fa', 'value' => '0', 'group' => 'security'],
            
            // Uploads
            ['key' => 'max_upload_size', 'value' => '10240', 'group' => 'uploads'], // KB
            ['key' => 'allowed_file_types', 'value' => 'pdf,doc,docx,xls,xlsx,jpg,png', 'group' => 'uploads'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
