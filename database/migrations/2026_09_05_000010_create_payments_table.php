<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30)->default('paymob');
            $table->string('paymob_order_id')->nullable();
            $table->string('paymob_transaction_id')->nullable();
            $table->string('method', 30);
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending');
            $table->string('transaction_reference')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'status']);
            $table->index('paymob_transaction_id');
            $table->index('paymob_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
