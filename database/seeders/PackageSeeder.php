<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Package;
use App\Models\Product;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Seed demo packages built from component products.
     */
    public function run(): void
    {
        $packages = [
            [
                'name_en' => '4 Camera CCTV Kit',
                'name_ar' => 'طقم مراقبة 4 كاميرات',
                'slug' => 'cctv-kit-4-cameras',
                'discount' => 600.00,
                'featured' => true,
                'items' => [
                    ['sku' => 'HIK-BUL-4MP', 'qty' => 4],
                    ['sku' => 'HIK-NVR-8CH', 'qty' => 1],
                    ['sku' => 'HIK-HDD-1TB', 'qty' => 1],
                    ['sku' => 'HIK-SW-8P', 'qty' => 1],
                    ['sku' => 'HIK-CAB-30M', 'qty' => 4],
                ],
            ],
            [
                'name_en' => '8 Camera Starter Kit',
                'name_ar' => 'طقم مراقبة 8 كاميرات للمبتدئين',
                'slug' => 'cctv-kit-8-cameras',
                'discount' => 1200.00,
                'featured' => true,
                'items' => [
                    ['sku' => 'HIK-BUL-4MP', 'qty' => 8],
                    ['sku' => 'HIK-NVR-16CH', 'qty' => 1],
                    ['sku' => 'HIK-HDD-2TB', 'qty' => 1],
                    ['sku' => 'HIK-SW-8P', 'qty' => 1],
                    ['sku' => 'HIK-CAB-30M', 'qty' => 8],
                ],
            ],
        ];

        foreach ($packages as $package) {
            $model = Package::query()->create([
                'name_ar' => $package['name_ar'],
                'name_en' => $package['name_en'],
                'slug' => $package['slug'],
                'description_ar' => 'باقة كاملة تشمل التركيب من كاميرات هايكفيجن.',
                'description_en' => 'Complete Hikvision kit ready for professional installation.',
                'base_price' => null,
                'use_component_pricing' => true,
                'discount_amount' => $package['discount'],
                'status' => ProductStatus::Active,
                'featured' => $package['featured'],
            ]);

            $total = 0.0;

            foreach ($package['items'] as $item) {
                $product = Product::query()->where('sku', $item['sku'])->firstOrFail();

                $model->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['qty'],
                ]);

                $total += $product->displayPrice() * $item['qty'];
            }

            $this->command?->info(sprintf(
                'Seeded package "%s" (components value: %.2f).',
                $package['name_en'],
                $total,
            ));
        }
    }
}
