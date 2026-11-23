<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\EmployeeSession;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminScreensTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_manager_can_open_all_enabled_admin_screens(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $permissionSlugs = [
            'dashboard.view',
            'branches.manage',
            'employees.manage',
            'roles.manage',
            'catalog.view',
            'catalog.manage',
            'delivery.manage',
            'orders.view',
            'orders.accept',
            'orders.cancel',
            'cashier.use',
            'printing.manage',
        ];

        $permissions = collect($permissionSlugs)->map(fn (string $slug) => Permission::query()->create([
            'name' => $slug,
            'slug' => $slug,
            'module' => 'Test',
        ]));

        $role = Role::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Branch Manager',
            'slug' => 'branch_manager',
            'is_system' => true,
        ]);
        $role->permissions()->sync($permissions->pluck('id'));

        $user = User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Manager',
            'phone' => '96569999999',
            'password' => 'manager-password-123',
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

        $this->actingAs($user)->withSession(['employee_session_token' => $token]);

        foreach ([
            '/admin/dashboard',
            '/admin/orders',
            '/admin/categories',
            '/admin/products',
            '/admin/delivery-areas',
            '/admin/cashier',
            '/admin/printers',
            '/admin/employees',
            '/admin/roles',
            '/admin/branches',
        ] as $path) {
            $this->get($path)->assertOk();
        }
    }
}
