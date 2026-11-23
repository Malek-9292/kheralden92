<?php

namespace Tests\Feature;

use App\Livewire\Admin\Orders\Index as OrdersIndex;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\EmployeeSession;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_livewire_advances_pickup_order_through_lifecycle_and_blocks_double_payment(): void
    {
        [$user, $token, $order] = $this->createStaffAndOrder();

        $this->actingAs($user)->withSession(['employee_session_token' => $token]);

        Livewire::test(OrdersIndex::class)
            ->call('advance', $order->id)
            ->assertHasNoErrors();
        $this->assertSame('confirmed', $order->fresh()->status);

        Livewire::test(OrdersIndex::class)->call('advance', $order->id);
        $this->assertSame('preparing', $order->fresh()->status);

        Livewire::test(OrdersIndex::class)->call('advance', $order->id);
        $this->assertSame('ready', $order->fresh()->status);

        Livewire::test(OrdersIndex::class)->call('collectCash', $order->id)->assertHasNoErrors();
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertNotNull($order->fresh()->paid_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.payment_collected',
            'auditable_id' => $order->id,
        ]);

        $this->assertDatabaseHas('print_jobs', [
            'order_id' => $order->id,
            'job_type' => 'customer_receipt',
        ]);

        $this->expectException(ValidationException::class);
        app(OrderWorkflow::class)->collectCash($order->fresh(), $user);
    }

    public function test_delivery_order_requires_out_for_delivery_before_cash_collection(): void
    {
        [$user, $token, $order] = $this->createStaffAndOrder(fulfillment: 'delivery', status: 'ready');

        $this->actingAs($user)->withSession(['employee_session_token' => $token]);

        try {
            app(OrderWorkflow::class)->collectCash($order, $user);
            $this->fail('Expected ValidationException before out_for_delivery.');
        } catch (ValidationException) {
            // expected
        }

        Livewire::test(OrdersIndex::class)->call('advance', $order->id);
        $this->assertSame('out_for_delivery', $order->fresh()->status);

        app(OrderWorkflow::class)->collectCash($order->fresh(), $user);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_livewire_cancel_requires_reason_and_persists_it(): void
    {
        [$user, $token, $order] = $this->createStaffAndOrder();

        $this->actingAs($user)->withSession(['employee_session_token' => $token]);

        Livewire::test(OrdersIndex::class)
            ->call('openCancelModal', $order->id)
            ->assertSet('cancelOrderId', $order->id)
            ->call('confirmCancel')
            ->assertHasErrors(['cancelReason']);

        $this->assertSame('pending', $order->fresh()->status);

        Livewire::test(OrdersIndex::class)
            ->call('openCancelModal', $order->id)
            ->set('cancelReason', 'طلب مكرر من العميل')
            ->call('confirmCancel')
            ->assertHasNoErrors()
            ->assertSet('cancelOrderId', null);

        $this->assertSame('cancelled', $order->fresh()->status);
        $log = AuditLog::query()
            ->where('action', 'order.cancelled')
            ->where('auditable_id', $order->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($log);
        $this->assertSame('طلب مكرر من العميل', data_get($log->after_values, 'reason'));
    }

    /**
     * @return array{0: User, 1: string, 2: Order}
     */
    private function createStaffAndOrder(string $fulfillment = 'pickup', string $status = 'pending'): array
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $permissions = collect([
            ['name' => 'Accept orders', 'slug' => 'orders.accept', 'module' => 'Orders'],
            ['name' => 'Cancel orders', 'slug' => 'orders.cancel', 'module' => 'Orders'],
            ['name' => 'Use cashier', 'slug' => 'cashier.use', 'module' => 'Cashier'],
            ['name' => 'View orders', 'slug' => 'orders.view', 'module' => 'Orders'],
        ])->map(fn (array $permission) => Permission::query()->create($permission));

        $role = Role::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Ops',
            'slug' => 'ops',
            'is_system' => true,
        ]);
        $role->permissions()->attach($permissions->pluck('id'));

        $user = User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Ops User',
            'phone' => '96562222222',
            'password' => 'ops-password-123456',
            'status' => 'active',
        ]);
        $user->roles()->attach($role->id);

        $token = Str::random(64);
        EmployeeSession::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'session_hash' => hash('sha256', $token),
            'logged_in_at' => now(),
            'last_activity_at' => now(),
        ]);

        $order = Order::query()->create([
            'branch_id' => $branch->id,
            'order_number' => 'KHAL-261123-000099',
            'customer_name' => 'عميل',
            'customer_phone' => '96560000000',
            'fulfillment_type' => $fulfillment,
            'status' => $status,
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'subtotal' => '3.000',
            'delivery_fee' => $fulfillment === 'delivery' ? '0.250' : '0.000',
            'minimum_order_snapshot' => '0.000',
            'total' => $fulfillment === 'delivery' ? '3.250' : '3.000',
            'tracking_token_hash' => hash('sha256', Str::random(64)),
        ]);

        return [$user, $token, $order];
    }
}
