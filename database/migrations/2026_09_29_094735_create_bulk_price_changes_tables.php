<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->uuid('pricing_revision')->nullable();
        });
        Schema::create('bulk_price_changes', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('options');
            $table->string('status')->default('preview');
            $table->timestamp('expires_at');
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('undone_at')->nullable();
            $table->timestamps();
        });
        Schema::create('bulk_price_change_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulk_price_change_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->string('sku');
            $table->json('before');
            $table->json('after');
            $table->uuid('applied_revision')->nullable();
            $table->string('status');
            $table->string('reason')->nullable();
            $table->unique(['bulk_price_change_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_price_change_items');
        Schema::dropIfExists('bulk_price_changes');
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('pricing_revision');
        });
    }
};
