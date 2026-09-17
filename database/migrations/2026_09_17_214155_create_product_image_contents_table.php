<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_image_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_image_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('mime_type', 32);
            $table->longText('contents');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_image_contents');
    }
};
