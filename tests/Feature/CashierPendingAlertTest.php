<?php

namespace Tests\Feature;

use App\Livewire\Admin\Cashier\Index as CashierIndex;
use App\Models\Branch;
use App\Models\EmployeeSession;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CashierPendingAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_shows_pending_orders_and_can_accept_them(): void
    {
        [$user, $token, $pending, $ready] = $this->createCashierWithOrders();

        $this->actingAs($user)->withSession(['employee_session_token' => $token]);

        Livewire::test(CashierIndex::class)
            ->assertSee($pending->order_number)
            ->assertSee('طلبات جديدة بانتظار القبول')
            ->assertSeeHtml('wire:poll.5s')
            ->call('acceptPending', $pending->id)
            ->assertHasNoErrors();

        $this->assertSame('confirmed', $pending->fresh()->status);
        $this->assertDatabaseHas('print_jobs', [
            'order_id' => $pending->id,
            'job_type' => 'kitchen_ticket',
        ]);

        Livewire::test(CashierIndex::class)
            ->assertSee($ready->order_number)
            ->set('soundMuted', false)
            ->call('toggleMute')
            ->assertSet('soundMuted', true);
    }

    /**
     * @return array{0: User, 1: string, 2: Order, 3: Order}
     */
    private function createCashierWithOrders(): array
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $permissions = collect([
            ['name' => 'Use cashier', 'slug' => 'cashier.use', 'module' => 'Cashier'],
            ['name' => 'Accept orders', 'slug' => 'orders.accept', 'module' => 'Orders'],
        ])->map(fn (array $permission) => Permission::query()->create($permission));

        $role = Role::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Cashier Ops',
            'slug' => 'cashier-ops',
            'is_system' => true,
        ]);
        $role->permissions()->attach($permissions->pluck('id'));

        $user = User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Cashier Ops',
            'phone' => '96565555555',
            'password' => 'cashier-ops-password',
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

        $pending = Order::query()->create([
            'branch_id' => $branch->id,
            'order_number' => 'KHAL-261123-000201',
            'customer_name' => 'عميل جديد',
            'customer_phone' => '96560000001',
            'fulfillment_type' => 'pickup',
            'status' => 'pending',
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'subtotal' => '4.000',
            'delivery_fee' => '0.000',
            'minimum_order_snapshot' => '0.000',
            'total' => '4.000',
            'tracking_token_hash' => hash('sha256', Str::random(64)),
        ]);

        $ready = Order::query()->create([
            'branch_id' => $branch->id,
            'order_number' => 'KHAL-261123-000202',
            'customer_name' => 'عميل جاهز',
            'customer_phone' => '96560000002',
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

        return [$user, $token, $pending, $ready];
    }
}
