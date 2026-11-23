<?php

namespace Tests\Feature\Storefront;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_checkout_with_same_idempotency_key_does_not_create_second_order(): void
    {
        [$branch, $product] = $this->createBranchAndProduct();

        $payload = [
            'customer_name' => 'عميل تجريبي',
            'customer_phone' => '96560000000',
            'fulfillment_type' => 'pickup',
            'idempotency_key' => 'checkout-key-abc-123456',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];

        $first = $this->postJson('/api/orders/KHAL', $payload)->assertCreated();
        $second = $this->postJson('/api/orders/KHAL', $payload)->assertOk();

        $this->assertSame($first->json('order.id'), $second->json('order.id'));
        $this->assertTrue($second->json('idempotent_replay'));
        $this->assertSame(1, Order::query()->where('branch_id', $branch->id)->count());
    }

    /**
     * @return array{0: Branch, 1: Product}
     */
    private function createBranchAndProduct(): array
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'branch_id' => $branch->id,
            'name' => 'العصائر',
            'slug' => 'juices',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'branch_id' => $branch->id,
            'category_id' => $category->id,
            'name' => 'برتقال',
            'slug' => 'orange',
            'price' => '0.600',
            'is_active' => true,
        ]);

        return [$branch, $product];
    }
}
