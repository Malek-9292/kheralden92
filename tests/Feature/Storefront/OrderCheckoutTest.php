<?php

namespace Tests\Feature\Storefront;

use App\Models\Branch;
use App\Models\Category;
use App\Models\DeliveryArea;
use App\Models\Order;
use App\Models\PrintJob;
use App\Models\Product;
use App\Services\OrderWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
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
            ->assertJsonPath('order.status', 'confirmed')
            ->assertJsonPath('order.delivery_fee', '0.000')
            ->assertJsonPath('order.total', '2.500')
            ->assertJsonPath('order.block', null)
            ->assertJsonPath('order.street', null);

        $orderId = $response->json('order.id');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.created',
            'auditable_id' => $orderId,
        ]);

        // Cash checkout auto-confirms and enqueues kitchen ticket immediately (no staff click).
        $this->assertDatabaseHas('print_jobs', [
            'order_id' => $orderId,
            'job_type' => 'kitchen_ticket',
            'status' => 'pending',
        ]);
        $this->assertNotNull(Order::query()->find($orderId)?->confirmed_at);

        // Customer storefront order tracking endpoint is removed.
        $this->getJson('/api/orders/KHAL/'.$response->json('order.order_number'))
            ->assertNotFound();
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

        $created = $this->postJson('/api/orders/KHAL', $payload)
            ->assertCreated()
            ->assertJsonPath('order.fulfillment_type', 'delivery')
            ->assertJsonPath('order.status', 'confirmed')
            ->assertJsonPath('order.delivery_fee', '1.000')
            ->assertJsonPath('order.subtotal', '5.000')
            ->assertJsonPath('order.total', '6.000')
            ->assertJsonPath('order.delivery_area_name', 'الخالدية');

        $this->assertDatabaseHas('print_jobs', [
            'order_id' => $created->json('order.id'),
            'job_type' => 'kitchen_ticket',
            'status' => 'pending',
        ]);
    }


    public function test_delivery_is_unavailable_before_1130_and_available_from_1130_kuwait_time(): void
    {
        [$branch, $product] = $this->createBranchAndProduct();

        $area = DeliveryArea::query()->create([
            'branch_id' => $branch->id,
            'governorate' => 'محافظة العاصمة',
            'name' => 'الخالدية',
            'delivery_fee' => '1.000',
            'minimum_order' => '0.000',
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

        Carbon::setTestNow(Carbon::parse('2026-11-24 11:29:59', 'Asia/Kuwait'));

        $this->postJson('/api/orders/KHAL', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fulfillment_type')
            ->assertJsonPath('errors.fulfillment_type.0', 'خدمة التوصيل تبدأ يوميًا الساعة 11:30 صباحًا.');

        Carbon::setTestNow(Carbon::parse('2026-11-24 11:30:00', 'Asia/Kuwait'));

        $this->postJson('/api/orders/KHAL', $payload)
            ->assertCreated()
            ->assertJsonPath('order.fulfillment_type', 'delivery');

        Carbon::setTestNow();
    }

    public function test_unpaid_online_order_stays_pending_without_kitchen_ticket_until_paid(): void
    {
        [$branch] = $this->createBranchAndProduct();

        $order = Order::query()->create([
            'branch_id' => $branch->id,
            'order_number' => 'KHAL-261123-000301',
            'customer_name' => 'عميل كي نت',
            'customer_phone' => '96560000099',
            'fulfillment_type' => 'pickup',
            'status' => 'pending',
            'payment_method' => 'knet',
            'payment_status' => 'unpaid',
            'subtotal' => '2.500',
            'delivery_fee' => '0.000',
            'minimum_order_snapshot' => '0.000',
            'total' => '2.500',
            'tracking_token_hash' => hash('sha256', Str::random(64)),
        ]);

        $workflow = app(OrderWorkflow::class);
        $this->assertFalse($workflow->shouldAcceptOnPlacement($order));

        $workflow->acceptOnPlacement($order);

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertDatabaseMissing('print_jobs', [
            'order_id' => $order->id,
            'job_type' => 'kitchen_ticket',
        ]);

        $order->update(['payment_status' => 'paid']);
        $this->assertTrue($workflow->shouldAcceptOnPlacement($order->fresh()));

        $workflow->acceptOnPlacement($order->fresh());

        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertDatabaseHas('print_jobs', [
            'order_id' => $order->id,
            'job_type' => 'kitchen_ticket',
            'status' => 'pending',
        ]);

        // Idempotent: second placement accept does not duplicate the kitchen job.
        $workflow->acceptOnPlacement($order->fresh());
        $this->assertSame(1, PrintJob::query()
            ->where('order_id', $order->id)
            ->where('job_type', 'kitchen_ticket')
            ->count());
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
