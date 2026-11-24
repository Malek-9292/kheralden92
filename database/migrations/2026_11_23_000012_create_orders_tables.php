<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('delivery_area_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number', 64)->unique();
            $table->string('customer_name', 150);
            $table->string('customer_phone', 30);
            $table->string('fulfillment_type', 20);
            $table->string('status', 30)->default('pending');
            $table->string('payment_method', 30)->default('cash');
            $table->string('payment_status', 20)->default('unpaid');
            $table->string('delivery_governorate', 100)->nullable();
            $table->string('delivery_area_name', 150)->nullable();
            $table->string('block', 30)->nullable();
            $table->string('street', 100)->nullable();
            $table->string('avenue', 100)->nullable();
            $table->string('house_number', 50)->nullable();
            $table->string('floor', 30)->nullable();
            $table->string('apartment', 30)->nullable();
            $table->text('customer_notes')->nullable();
            $table->decimal('subtotal', 12, 3);
            $table->decimal('delivery_fee', 12, 3)->default(0);
            $table->decimal('minimum_order_snapshot', 12, 3)->default(0);
            $table->decimal('total', 12, 3);
            $table->char('tracking_token_hash', 64);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['branch_id', 'status', 'created_at']);
            $table->index(['branch_id', 'fulfillment_type', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name', 180);
            $table->string('category_name', 150);
            $table->decimal('unit_price', 12, 3);
            $table->unsignedSmallInteger('quantity');
            $table->decimal('line_total', 12, 3);
            $table->timestamps();
            $table->index(['order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
