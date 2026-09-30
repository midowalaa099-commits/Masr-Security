<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        $brandNames = collect(['Hikvision', 'HiLook', 'EZVIZ'])
            ->merge(DB::table('products')
                ->whereNotNull('brand')
                ->pluck('brand')
                ->map(fn (string $brand): string => trim($brand))
                ->filter(fn (string $brand): bool => $brand !== '' && mb_strlen($brand) <= 100 && mb_strtolower($brand) !== 'dahua'))
            ->unique(fn (string $brand): string => mb_strtolower($brand))
            ->values();

        DB::table('product_brands')->insert($brandNames->map(fn (string $name): array => [
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_brands');
    }
};
