<?php

namespace Tests\Feature;

use App\Livewire\Admin\Dashboard;
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

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_counts_recent_orders_and_quick_links(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        foreach (['dashboard.view', 'orders.view', 'employees.manage', 'catalog.view'] as $slug) {
            Permission::query()->create(['name' => $slug, 'slug' => $slug, 'module' => 'Test']);
        }

        $role = Role::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Manager',
            'slug' => 'branch_manager',
            'is_system' => true,
        ]);
        $role->permissions()->sync(Permission::query()->pluck('id'));

        $user = User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Manager',
            'phone' => '96567770000',
            'password' => 'manager-password-12',
            'status' => 'active',
        ]);
        $user->roles()->attach($role->id);

        Order::query()->create([
            'branch_id' => $branch->id,
            'order_number' => 'KHAL-DASH-1',
            'customer_name' => 'عميل لوحة',
            'customer_phone' => '96560001111',
            'fulfillment_type' => 'pickup',
            'status' => 'pending',
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'subtotal' => '1.500',
            'delivery_fee' => '0.000',
            'minimum_order_snapshot' => '0.000',
            'total' => '1.500',
            'tracking_token_hash' => hash('sha256', 'dash'),
        ]);

        $token = Str::random(64);
        EmployeeSession::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'session_hash' => hash('sha256', $token),
            'logged_in_at' => now(),
            'last_activity_at' => now(),
        ]);

        $this->actingAs($user)->withSession(['employee_session_token' => $token]);

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('الطلبات الجديدة')
            ->assertSee('طلبات اليوم')
            ->assertSee('KHAL-DASH-1')
            ->assertSee('الطلبات')
            ->assertSee('الموظفون')
            ->assertSee('الأصناف');

        Livewire::test(Dashboard::class)
            ->assertSee('KHAL-DASH-1')
            ->assertSee('جديد');
    }
}
