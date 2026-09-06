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
            'phone' => '',
            'whatsapp' => '',
            'email' => '',
            'address' => '',
            'shipping_fee' => '60',
            'facebook' => 'https://www.facebook.com/profile.php?id=61586652051946&locale=ar_AR',
            'instagram' => '',
            'google_maps' => '',
            'hero_title_en' => 'Professional Security Systems for Home & Business',
            'hero_title_ar' => 'أنظمة أمنية احترافية للمنازل والشركات',
            'hero_subtitle_en' => 'Authorized Hikvision distributor. Security cameras, NVR/DVR recorders, interactive displays and professional solutions.',
            'hero_subtitle_ar' => 'موزع معتمد من هايكفيجن. كاميرات مراقبة، مسجلات NVR/DVR، شاشات تفاعلية وحلول احترافية.',
            'site_logo' => '/images/branding/masr-security-logo.jpg',
            'hero_image' => '/images/branding/masr-security-hero.jpg',
            'gallery_images' => '[]',
            'why_points_en' => "Security cameras, NVR/DVR recorders and accessories\r\nInteractive display solutions\r\nProfessional security systems for home and business",
            'why_points_ar' => "كاميرات مراقبة ومسجلات وإكسسوارات\r\nحلول الشاشات التفاعلية\r\nأنظمة أمنية احترافية للمنازل والشركات",
        ]);
    }
}
