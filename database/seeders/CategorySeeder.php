<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed a realistic category tree for a security systems distributor.
     */
    public function run(): void
    {
        $categories = [
            'cctv-cameras' => [
                'name_en' => 'CCTV Cameras',
                'name_ar' => 'كاميرات المراقبة',
                'children' => [
                    'bullet-cameras' => ['Bullet Cameras', 'كاميرات خارجية'],
                    'dome-cameras' => ['Dome Cameras', 'كاميرات قبة'],
                    'ptz-cameras' => ['PTZ Cameras', 'كاميرات PTZ'],
                    'wireless-cameras' => ['Wireless Cameras', 'كاميرات لاسلكية'],
                ],
            ],
            'nvr-dvr' => [
                'name_en' => 'NVR / DVR Recorders',
                'name_ar' => 'مسجلات NVR / DVR',
                'children' => [],
            ],
            'access-control' => [
                'name_en' => 'Access Control',
                'name_ar' => 'أنظمة التحكم في الدخول',
                'children' => [],
            ],
            'interactive-displays' => [
                'name_en' => 'Interactive Displays',
                'name_ar' => 'الشاشات التفاعلية',
                'children' => [],
            ],
            'networking' => [
                'name_en' => 'Networking',
                'name_ar' => 'الشبكات',
                'children' => [],
            ],
            'storage' => [
                'name_en' => 'Storage & Accessories',
                'name_ar' => 'التخزين والإكسسوارات',
                'children' => [],
            ],
        ];

        $sort = 1;

        foreach ($categories as $slug => $data) {
            $parent = Category::query()->create([
                'name_en' => $data['name_en'],
                'name_ar' => $data['name_ar'],
                'slug' => $slug,
                'is_active' => true,
                'sort_order' => $sort++,
            ]);

            foreach ($data['children'] as $childSlug => [$childEn, $childAr]) {
                Category::query()->create([
                    'name_en' => $childEn,
                    'name_ar' => $childAr,
                    'slug' => $childSlug,
                    'parent_id' => $parent->id,
                    'is_active' => true,
                    'sort_order' => $sort++,
                ]);
            }
        }
    }
}
