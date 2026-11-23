<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrintJob;
use App\Services\PrintJobDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PrintJobDispatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_kitchen_ticket_payload_includes_arabic_lines_and_reprint_requeues(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'branch_id' => $branch->id,
            'order_number' => 'KHAL-261123-000010',
            'customer_name' => 'عميل',
            'customer_phone' => '96560000000',
            'fulfillment_type' => 'pickup',
            'status' => 'pending',
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'subtotal' => '1.500',
            'delivery_fee' => '0.000',
            'minimum_order_snapshot' => '0.000',
            'total' => '1.500',
            'tracking_token_hash' => hash('sha256', Str::random(64)),
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name' => 'شاورما دجاج',
            'category_name' => 'الساندويتشات الحارة والباردة',
            'unit_price' => '0.650',
            'quantity' => 2,
            'line_total' => '1.300',
        ]);

        $dispatcher = app(PrintJobDispatcher::class);
        $job = $dispatcher->enqueueKitchenTicket($order->fresh()->load('items', 'branch'));

        $this->assertSame('kitchen_ticket', $job->job_type);
        $this->assertContains('تذكرة المطبخ', $job->payload['lines']);
        $this->assertTrue(collect($job->payload['lines'])->contains(fn ($line) => str_contains((string) $line, 'شاورما دجاج')));

        $job->update(['status' => 'completed', 'completed_at' => now()]);

        $reprinted = $dispatcher->reprintKitchenTicket($order->fresh()->load('items', 'branch'));
        $this->assertSame($job->id, $reprinted->id);
        $this->assertSame('pending', $reprinted->status);
        $this->assertSame(1, PrintJob::query()->where('order_id', $order->id)->where('job_type', 'kitchen_ticket')->count());
    }
}
