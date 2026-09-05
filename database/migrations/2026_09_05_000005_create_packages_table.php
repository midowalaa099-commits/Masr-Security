<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->string('cover_image')->nullable();
            $table->decimal('base_price', 12, 2)->nullable();
            $table->boolean('use_component_pricing')->default(true);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->boolean('featured')->default(false);
            $table->timestamps();

            $table->index('status');
            $table->index('featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
