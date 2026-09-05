<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed realistic demo products for the storefront.
     */
    public function run(): void
    {
        $find = fn (string $slug) => Category::query()->where('slug', $slug)->firstOrFail();

        $products = [
            [
                'category' => 'bullet-cameras', 'sku' => 'HIK-BUL-4MP', 'type' => ProductType::Component,
                'name_en' => 'Hikvision 4MP Bullet Camera', 'name_ar' => 'كاميرا هايكفيجن خارجية 4 ميجابيكسل',
                'model' => 'DS-2CD2047G2-L', 'price' => 1850.00, 'stock' => 40, 'featured' => true,
            ],
            [
                'category' => 'bullet-cameras', 'sku' => 'HIK-BUL-8MP', 'type' => ProductType::Simple,
                'name_en' => 'Hikvision 8MP ColorVu Bullet Camera', 'name_ar' => 'كاميرا خارجية ColorVu 8 ميجابيكسل',
                'model' => 'DS-2CD2087G2-LU', 'price' => 3200.00, 'sale' => 2850.00, 'stock' => 15, 'featured' => true,
            ],
            [
                'category' => 'dome-cameras', 'sku' => 'HIK-DOM-4MP', 'type' => ProductType::Simple,
                'name_en' => 'Hikvision 4MP Dome Camera', 'name_ar' => 'كاميرا قبة 4 ميجابيكسل',
                'model' => 'DS-2CD2147G2H', 'price' => 1650.00, 'stock' => 35, 'featured' => false,
            ],
            [
                'category' => 'ptz-cameras', 'sku' => 'HIK-PTZ-2MP', 'type' => ProductType::Simple,
                'name_en' => 'Hikvision 2MP PTZ Camera 25x Zoom', 'name_ar' => 'كاميرا PTZ 2 ميجابيكسل زوم 25x',
                'model' => 'DS-2DE2A400IW-DE3', 'price' => 8500.00, 'stock' => 8, 'featured' => false,
            ],
            [
                'category' => 'wireless-cameras', 'sku' => 'HIK-WDC-2MP', 'type' => ProductType::Simple,
                'name_en' => 'Hikvision 2MP WiFi Camera', 'name_ar' => 'كاميرا واي فاي 2 ميجابيكسل',
                'model' => 'DS-2CV2U21FD-IW', 'price' => 2100.00, 'stock' => 0, 'featured' => false,
            ],
            [
                'category' => 'nvr-dvr', 'sku' => 'HIK-NVR-8CH', 'type' => ProductType::Component,
                'name_en' => 'Hikvision 8-Channel NVR', 'name_ar' => 'مسجل NVR 8 قناة',
                'model' => 'DS-7608NXI-K1', 'price' => 2950.00, 'stock' => 25, 'featured' => false,
            ],
            [
                'category' => 'nvr-dvr', 'sku' => 'HIK-NVR-16CH', 'type' => ProductType::Simple,
                'name_en' => 'Hikvision 16-Channel NVR', 'name_ar' => 'مسجل NVR 16 قناة',
                'model' => 'DS-7616NXI-K2', 'price' => 3950.00, 'stock' => 12, 'featured' => true,
            ],
            [
                'category' => 'nvr-dvr', 'sku' => 'HIK-DVR-8CH', 'type' => ProductType::Simple,
                'name_en' => 'Hikvision 8-Channel TurboHD DVR', 'name_ar' => 'مسجل DVR 8 قناة',
                'model' => 'DS-7108HGHI-K1', 'price' => 1850.00, 'stock' => 20, 'featured' => false,
            ],
            [
                'category' => 'access-control', 'sku' => 'HIK-ACC-FINGER', 'type' => ProductType::Simple,
                'name_en' => 'Hikvision Face & Fingerprint Terminal', 'name_ar' => 'جهاز حضور وبصمة هايكفيجن',
                'model' => 'DS-K1T804A', 'price' => 7400.00, 'stock' => 10, 'featured' => false,
            ],
            [
                'category' => 'interactive-displays', 'sku' => 'HIK-IDIS-65', 'type' => ProductType::Simple,
                'name_en' => 'Hikvision 65" Interactive Display', 'name_ar' => 'شاشة تفاعلية 65 بوصة',
                'model' => 'DS-D5A65RB', 'price' => 42500.00, 'stock' => 5, 'featured' => true,
            ],
            [
                'category' => 'networking', 'sku' => 'HIK-SW-8P', 'type' => ProductType::Component,
                'name_en' => '8-Port PoE Switch', 'name_ar' => 'سويتش PoE 8 منافذ',
                'model' => 'DS-3E0510P-E', 'price' => 1450.00, 'stock' => 50, 'featured' => false,
            ],
            [
                'category' => 'storage', 'sku' => 'HIK-HDD-1TB', 'type' => ProductType::Component,
                'name_en' => '1TB Surveillance HDD', 'name_ar' => 'هارد ديسك 1 تيرا للتسجيل',
                'model' => 'WD-PURPLE-1TB', 'price' => 1650.00, 'stock' => 60, 'featured' => false,
            ],
            [
                'category' => 'storage', 'sku' => 'HIK-HDD-2TB', 'type' => ProductType::Simple,
                'name_en' => '2TB Surveillance HDD', 'name_ar' => 'هارد ديسك 2 تيرا للتسجيل',
                'model' => 'WD-PURPLE-2TB', 'price' => 2400.00, 'sale' => 2150.00, 'stock' => 40, 'featured' => false,
            ],
            [
                'category' => 'storage', 'sku' => 'HIK-CAB-30M', 'type' => ProductType::Component,
                'name_en' => '30m CCTV Cable Kit', 'name_ar' => 'كابل مراقبة 30 متر',
                'model' => 'CABLE-30M', 'price' => 450.00, 'stock' => 100, 'featured' => false,
            ],
        ];

        foreach ($products as $i => $product) {
            Product::query()->create([
                'category_id' => $find($product['category'])->id,
                'sku' => $product['sku'],
                'name_ar' => $product['name_ar'],
                'name_en' => $product['name_en'],
                'slug' => $product['sku'],
                'description_ar' => 'وصف تجريبي لـ '.$product['name_ar'],
                'description_en' => 'Demo description for '.$product['name_en'],
                'brand' => 'Hikvision',
                'model_number' => $product['model'],
                'price' => $product['price'],
                'sale_price' => $product['sale'] ?? null,
                'stock_quantity' => $product['stock'],
                'low_stock_threshold' => 5,
                'status' => ProductStatus::Active,
                'type' => $product['type'],
                'featured' => $product['featured'],
            ]);
        }

        $this->command?->info('Seeded '.count($products).' demo products.');
    }
}
