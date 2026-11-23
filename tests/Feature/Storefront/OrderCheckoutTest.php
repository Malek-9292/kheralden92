<?php

namespace Tests\Feature\Storefront;

use App\Models\Branch;
use App\Models\Category;
use App\Models\DeliveryArea;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_pickup_order_needs_no_address_and_has_no_delivery_minimum_or_fee(): void
    {
        [$branch, $product] = $this->createBranchAndProduct();

        $response = $this->postJson('/api/orders/KHAL', [
            'customer_name' => 'عميل تجريبي',
            'customer_phone' => '96560000000',
            'fulfillment_type' => 'pickup',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('order.fulfillment_type', 'pickup')
            ->assertJsonPath('order.delivery_fee', '0.000')
            ->assertJsonPath('order.total', '2.500')
            ->assertJsonPath('order.block', null)
            ->assertJsonPath('order.street', null);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.created',
            'auditable_id' => $response->json('order.id'),
        ]);

        $this->assertDatabaseHas('print_jobs', [
            'order_id' => $response->json('order.id'),
            'job_type' => 'kitchen_ticket',
            'status' => 'pending',
        ]);

        $orderNumber = $response->json('order.order_number');
        $trackingToken = $response->json('tracking_token');

        $this->withHeader('X-Order-Tracking-Token', $trackingToken)
            ->getJson("/api/orders/KHAL/{$orderNumber}")
            ->assertOk()
            ->assertJsonPath('order.order_number', $orderNumber);
    }

    public function test_delivery_order_enforces_area_minimum_and_calculates_fee_on_server(): void
    {
        [$branch, $product] = $this->createBranchAndProduct();

        $area = DeliveryArea::query()->create([
            'branch_id' => $branch->id,
            'governorate' => 'محافظة العاصمة',
            'name' => 'الخالدية',
            'delivery_fee' => '1.000',
            'minimum_order' => '5.000',
            'is_active' => true,
        ]);

        $payload = [
            'customer_name' => 'عميل تجريبي',
            'customer_phone' => '96560000000',
            'fulfillment_type' => 'delivery',
            'delivery_area_id' => $area->id,
            'block' => '2',
            'street' => '10',
            'house_number' => '15',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];

        $this->postJson('/api/orders/KHAL', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $payload['items'][0]['quantity'] = 2;

        $this->postJson('/api/orders/KHAL', $payload)
            ->assertCreated()
            ->assertJsonPath('order.fulfillment_type', 'delivery')
            ->assertJsonPath('order.delivery_fee', '1.000')
            ->assertJsonPath('order.subtotal', '5.000')
            ->assertJsonPath('order.total', '6.000')
            ->assertJsonPath('order.delivery_area_name', 'الخالدية');
    }

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
            'name' => 'الساندويتشات',
            'slug' => 'sandwiches',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'branch_id' => $branch->id,
            'category_id' => $category->id,
            'name' => 'ساندويتش تجريبي',
            'slug' => 'test-sandwich',
            'price' => '2.500',
            'is_active' => true,
        ]);

        return [$branch, $product];
    }
}
