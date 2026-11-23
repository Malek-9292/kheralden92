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

class AdminSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_search_section_links_to_existing_admin_routes(): void
    {
        [$user, $token] = $this->actingEmployeeWithPermissions(['catalog.view']);

        $this->actingAs($user)->withSession(['employee_session_token' => $token]);

        $response = $this->getJson('/admin/search?q=الأصناف')->assertOk();
        $urls = collect($response->json('results.sections') ?? [])->pluck('url')->all();

        $this->assertContains('/admin/products', $urls);
        $this->assertNotContains('/admin/catalog', $urls);

        $categories = $this->getJson('/admin/search?q=الأقسام')->assertOk();
        $categoryUrls = collect($categories->json('results.sections') ?? [])->pluck('url')->all();
        $this->assertContains('/admin/categories', $categoryUrls);
    }

    public function test_search_does_not_leak_orders_or_employees_without_permission(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        Order::query()->create([
            'branch_id' => $branch->id,
            'order_number' => 'KHAL-SEARCH-1',
            'customer_name' => 'عميل بحث',
            'customer_phone' => '96550001111',
            'fulfillment_type' => 'pickup',
            'status' => 'pending',
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'subtotal' => '1.000',
            'delivery_fee' => '0.000',
            'minimum_order_snapshot' => '0.000',
            'total' => '1.000',
            'tracking_token_hash' => hash('sha256', 'token'),
        ]);

        User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'موظف مخفي',
            'phone' => '96550002222',
            'password' => 'hidden-password-12',
            'status' => 'active',
        ]);

        [$user, $token] = $this->actingEmployeeWithPermissions(['catalog.view'], $branch);

        $this->actingAs($user)->withSession(['employee_session_token' => $token]);

        $response = $this->getJson('/admin/search?q=بحث')->assertOk();
        $results = $response->json('results');

        $this->assertArrayNotHasKey('orders', $results);
        $this->assertArrayNotHasKey('employees', $results);
        $this->assertArrayNotHasKey('branches', $results);
    }

    public function test_branch_manager_search_is_scoped_to_own_branch_orders(): void
    {
        $branchA = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);
        $branchB = Branch::query()->create([
            'code' => 'SALM',
            'name' => 'فرع السالمية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        Order::query()->create([
            'branch_id' => $branchA->id,
            'order_number' => 'OWN-ORDER-100',
            'customer_name' => 'عميل أ',
            'customer_phone' => '96551110000',
            'fulfillment_type' => 'pickup',
            'status' => 'pending',
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'subtotal' => '2.000',
            'delivery_fee' => '0.000',
            'minimum_order_snapshot' => '0.000',
            'total' => '2.000',
            'tracking_token_hash' => hash('sha256', 'a'),
        ]);

        Order::query()->create([
            'branch_id' => $branchB->id,
            'order_number' => 'OTHER-ORDER-200',
            'customer_name' => 'عميل ب',
            'customer_phone' => '96552220000',
            'fulfillment_type' => 'pickup',
            'status' => 'pending',
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'subtotal' => '3.000',
            'delivery_fee' => '0.000',
            'minimum_order_snapshot' => '0.000',
            'total' => '3.000',
            'tracking_token_hash' => hash('sha256', 'b'),
        ]);

        [$user, $token] = $this->actingEmployeeWithPermissions(['orders.view'], $branchA);
        $this->actingAs($user)->withSession(['employee_session_token' => $token]);

        $response = $this->getJson('/admin/search?q=ORDER')->assertOk();
        $titles = collect($response->json('results.orders') ?? [])->pluck('title')->all();

        $this->assertContains('OWN-ORDER-100', $titles);
        $this->assertNotContains('OTHER-ORDER-200', $titles);
    }

    /**
     * @param  list<string>  $permissionSlugs
     * @return array{0: User, 1: string}
     */
    private function actingEmployeeWithPermissions(array $permissionSlugs, ?Branch $branch = null): array
    {
        $branch ??= Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $permissions = collect($permissionSlugs)->map(fn (string $slug) => Permission::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug, 'module' => 'Test'],
        ));

        $role = Role::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Role '.Str::random(5),
            'slug' => 'role_'.Str::lower(Str::random(6)),
            'is_system' => true,
        ]);
        $role->permissions()->sync($permissions->pluck('id'));

        $user = User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Tester',
            'phone' => '9656'.random_int(1000000, 9999999),
            'password' => 'tester-password-12',
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

        return [$user, $token];
    }
}
