<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\EmployeeSession;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CashierCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_collect_payment_for_a_ready_pickup_order_only_once(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $permission = Permission::query()->create([
            'name' => 'Use cashier',
            'slug' => 'cashier.use',
            'module' => 'Cashier',
        ]);

        $role = Role::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Cashier',
            'slug' => 'cashier',
            'is_system' => true,
        ]);
        $role->permissions()->attach($permission->id);

        $cashier = User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Cashier Test',
            'phone' => '96561111111',
            'password' => 'cashier-password-123',
            'status' => 'active',
        ]);
        $cashier->roles()->attach($role->id);

        $token = Str::random(64);
        EmployeeSession::query()->create([
            'user_id' => $cashier->id,
            'branch_id' => $branch->id,
            'session_hash' => hash('sha256', $token),
            'logged_in_at' => now(),
            'last_activity_at' => now(),
        ]);

        $order = Order::query()->create([
            'branch_id' => $branch->id,
            'order_number' => 'KHAL-261123-000001',
            'customer_name' => 'عميل',
            'customer_phone' => '96560000000',
            'fulfillment_type' => 'pickup',
            'status' => 'ready',
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'subtotal' => '5.000',
            'delivery_fee' => '0.000',
            'minimum_order_snapshot' => '0.000',
            'total' => '5.000',
            'tracking_token_hash' => hash('sha256', Str::random(64)),
        ]);

        $this->actingAs($cashier)
            ->withSession(['employee_session_token' => $token])
            ->postJson("/admin/cashier/orders/{$order->id}/collect-cash")
            ->assertOk()
            ->assertJsonPath('order.payment_status', 'paid');

        $this->assertNotNull($order->fresh()->paid_at);

        $this->actingAs($cashier)
            ->withSession(['employee_session_token' => $token])
            ->postJson("/admin/cashier/orders/{$order->id}/collect-cash")
            ->assertUnprocessable();
    }
}
