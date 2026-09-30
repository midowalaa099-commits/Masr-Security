<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\Package;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\User;
use Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LiteralSearchTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('searchCases')]
    public function test_search_is_case_insensitive_and_treats_wildcards_literally(
        string $route,
        string $model,
        string $collection,
        string $field,
        string $value,
        string $search,
        string $decoy,
    ): void {
        $this->actingAs(User::factory()->admin()->create());
        $matched = $model::factory()->create([$field => $value]);
        $model::factory()->create([$field => $decoy]);

        $parameter = $route === 'shop' ? 'q' : 'search';

        $this->get(route($route, [$parameter => $search]))
            ->assertOk()
            ->assertViewHas($collection, fn (LengthAwarePaginator $items): bool => $items->getCollection()->modelKeys() === [$matched->getKey()]);
    }

    public static function searchCases(): Generator
    {
        $endpoints = [
            ['shop', Product::class, 'products', ['name_en', 'name_ar', 'sku', 'model_number']],
            ['admin.products.index', Product::class, 'products', ['name_en', 'name_ar', 'sku']],
            ['admin.categories.index', Category::class, 'categories', ['name_en', 'name_ar']],
            ['admin.packages.index', Package::class, 'packages', ['name_en', 'name_ar']],
            ['admin.orders.index', Order::class, 'orders', ['order_number', 'phone', 'customer_name']],
            ['admin.quotes.index', QuoteRequest::class, 'quotes', ['name', 'company', 'phone']],
            ['admin.audit-logs.index', AuditLog::class, 'logs', ['action']],
        ];
        $patterns = [
            'lowercase' => ['MiXeDCamera', 'mixedcamera', 'Unrelated'],
            'uppercase' => ['MiXeDCamera', 'MIXEDCAMERA', 'Unrelated'],
            'percent' => ['Marker%X', 'marker%x', 'MarkerAnyX'],
            'underscore' => ['Marker_X', 'marker_x', 'MarkerAX'],
            'backslash' => ['Marker\\X', 'marker\\x', 'MarkerX'],
            'quote' => ["O'Reilly", "o'reilly", 'OReilly'],
        ];

        foreach ($endpoints as [$route, $model, $collection, $fields]) {
            foreach ($fields as $field) {
                foreach ($patterns as $case => [$value, $search, $decoy]) {
                    yield "$route/$field/$case" => [$route, $model, $collection, $field, $value, $search, $decoy];
                }
            }
        }
    }
}
