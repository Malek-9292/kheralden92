<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Printer;
use App\Models\PrintJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PrintAgentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_kitchen_printer_can_claim_and_complete_a_print_job(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $token = Str::random(64);
        $printer = Printer::query()->create([
            'branch_id' => $branch->id,
            'code' => 'KITCHEN-01',
            'name' => 'طابعة المطبخ',
            'purpose' => 'kitchen',
            'connection_type' => 'usb',
            'device_token_hash' => hash('sha256', $token),
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'branch_id' => $branch->id,
            'order_number' => 'KHAL-261123-000001',
            'customer_name' => 'عميل',
            'customer_phone' => '96560000000',
            'fulfillment_type' => 'pickup',
            'status' => 'pending',
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'subtotal' => '5.000',
            'delivery_fee' => '0.000',
            'minimum_order_snapshot' => '0.000',
            'total' => '5.000',
            'tracking_token_hash' => hash('sha256', Str::random(64)),
        ]);

        $job = PrintJob::query()->create([
            'branch_id' => $branch->id,
            'printer_id' => null,
            'order_id' => $order->id,
            'job_type' => 'kitchen_ticket',
            'status' => 'pending',
            'payload' => ['order_number' => $order->order_number, 'items' => []],
            'available_at' => now(),
        ]);

        $headers = ['X-Printer-Code' => 'KITCHEN-01', 'X-Printer-Token' => $token];

        $this->withHeaders($headers)->postJson('/api/print-agent/jobs/claim')
            ->assertOk()
            ->assertJsonPath('job.id', $job->id)
            ->assertJsonPath('job.payload.order_number', $order->order_number);

        $this->assertDatabaseHas('print_jobs', [
            'id' => $job->id,
            'printer_id' => $printer->id,
            'status' => 'processing',
            'attempts' => 1,
        ]);

        $this->withHeaders($headers)->postJson("/api/print-agent/jobs/{$job->id}/complete")
            ->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('idempotent', false);

        $this->assertDatabaseHas('print_jobs', [
            'id' => $job->id,
            'status' => 'completed',
        ]);

        $this->withHeaders($headers)->postJson("/api/print-agent/jobs/{$job->id}/complete")
            ->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('idempotent', true);

        $this->withHeaders($headers)
            ->postJson("/api/print-agent/jobs/{$job->id}/fail", ['error' => 'late fail after success'])
            ->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('idempotent', true);

        $this->assertDatabaseHas('print_jobs', [
            'id' => $job->id,
            'status' => 'completed',
        ]);
    }

    public function test_fail_on_processing_job_is_idempotent_when_already_pending(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $token = Str::random(64);
        $printer = Printer::query()->create([
            'branch_id' => $branch->id,
            'code' => 'KITCHEN-01',
            'name' => 'طابعة المطبخ',
            'purpose' => 'kitchen',
            'connection_type' => 'usb',
            'device_token_hash' => hash('sha256', $token),
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'branch_id' => $branch->id,
            'order_number' => 'KHAL-261123-000099',
            'customer_name' => 'عميل',
            'customer_phone' => '96560000099',
            'fulfillment_type' => 'pickup',
            'status' => 'pending',
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'subtotal' => '2.000',
            'delivery_fee' => '0.000',
            'minimum_order_snapshot' => '0.000',
            'total' => '2.000',
            'tracking_token_hash' => hash('sha256', Str::random(64)),
        ]);

        $job = PrintJob::query()->create([
            'branch_id' => $branch->id,
            'printer_id' => $printer->id,
            'order_id' => $order->id,
            'job_type' => 'kitchen_ticket',
            'status' => 'processing',
            'attempts' => 1,
            'payload' => ['order_number' => $order->order_number],
            'available_at' => now(),
            'claimed_at' => now(),
        ]);

        $headers = ['X-Printer-Code' => 'KITCHEN-01', 'X-Printer-Token' => $token];

        $this->withHeaders($headers)
            ->postJson("/api/print-agent/jobs/{$job->id}/fail", ['error' => 'USB disconnected'])
            ->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('idempotent', false);

        $this->withHeaders($headers)
            ->postJson("/api/print-agent/jobs/{$job->id}/fail", ['error' => 'duplicate fail'])
            ->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('idempotent', true);
    }

    public function test_invalid_printer_token_cannot_claim_jobs(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        Printer::query()->create([
            'branch_id' => $branch->id,
            'code' => 'KITCHEN-01',
            'name' => 'طابعة المطبخ',
            'purpose' => 'kitchen',
            'connection_type' => 'usb',
            'device_token_hash' => hash('sha256', 'real-secret-token'),
            'is_active' => true,
        ]);

        $this->withHeaders([
            'X-Printer-Code' => 'KITCHEN-01',
            'X-Printer-Token' => 'wrong-token',
        ])->postJson('/api/print-agent/jobs/claim')->assertUnauthorized();
    }
}
