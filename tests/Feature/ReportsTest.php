<?php

namespace Tests\Feature;

use App\Livewire\Admin\Reports\Index as ReportsIndex;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_report_summarizes_paid_orders_and_top_products(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $permission = Permission::query()->create([
            'name' => 'View reports',
            'slug' => 'reports.view',
            'module' => 'Reports',
        ]);

        $role = Role::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Report Viewer',
            'slug' => 'report_viewer',
            'is_system' => false,
        ]);
        $role->permissions()->attach($permission->id);

        $user = User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Report User',
            'phone' => '96567777777',
            'password' => 'report-password-123',
            'status' => 'active',
        ]);
        $user->roles()->attach($role->id);
        $this->actingAs($user);

        $order = Order::query()->create([
            'branch_id' => $branch->id,
            'order_number' => 'KHAL-REPORT-001',
            'customer_name' => 'عميل',
            'customer_phone' => '96560000000',
            'fulfillment_type' => 'pickup',
            'status' => 'completed',
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'subtotal' => '3.000',
            'delivery_fee' => '0.000',
            'minimum_order_snapshot' => '0.000',
            'total' => '3.000',
            'tracking_token_hash' => hash('sha256', 'report-tracking-token'),
            'paid_at' => now(),
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name' => 'ساندويتش تجريبي',
            'category_name' => 'الساندويتشات',
            'unit_price' => '3.000',
            'quantity' => 1,
            'line_total' => '3.000',
        ]);

        Livewire::test(ReportsIndex::class)
            ->assertSee('إجمالي الطلبات')
            ->assertSee('المبيعات المحصلة')
            ->assertSee('ساندويتش تجريبي')
            ->assertSee('3.000')
            ->assertSee('تصدير CSV');

        $response = Livewire::test(ReportsIndex::class)->call('exportCsv');
        $response->assertFileDownloaded('fatoosh-orders-'.now('Asia/Kuwait')->startOfMonth()->toDateString().'_'.now('Asia/Kuwait')->toDateString().'.csv');
    }
}
