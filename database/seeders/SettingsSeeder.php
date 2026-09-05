<?php

namespace Database\Seeders;

use App\Services\SettingsService;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(SettingsService $settings): void
    {
        $settings->setMany([
            'company_name' => 'MASR Security',
            'phone' => '+20 100 000 0000',
            'whatsapp' => '+20 100 000 0000',
            'email' => 'info@masr-security.com',
            'address' => 'Cairo, Egypt',
            'shipping_fee' => '60',
            'facebook' => 'https://facebook.com',
            'instagram' => 'https://instagram.com',
            'google_maps' => '',
            'hero_title_en' => 'Professional Security Systems for Home & Business',
            'hero_title_ar' => 'أنظمة أمنية احترافية للمنازل والشركات',
            'hero_subtitle_en' => 'Hikvision-authorized distributors. Cameras, recorders, access control and complete installation.',
            'hero_subtitle_ar' => 'موزعون معتمدون من هايكفيجن. كاميرات، مسجلات، تحكم في الدخول وتركيب كامل.',
        ]);
    }
}
