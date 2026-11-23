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

class RolePermissionGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_manager_cannot_assign_system_module_permission(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $manageRoles = Permission::query()->create([
            'name' => 'Manage roles',
            'slug' => 'roles.manage',
            'module' => 'AccessControl',
        ]);
        Permission::query()->create([
            'name' => 'Manage modules',
            'slug' => 'modules.manage',
            'module' => 'System',
        ]);

        $role = Role::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Branch Manager',
            'slug' => 'branch_manager',
            'is_system' => true,
        ]);
        $role->permissions()->attach($manageRoles->id);

        $user = User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Manager',
            'phone' => '96563333333',
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

        $this->actingAs($user)->withSession(['employee_session_token' => $token])
            ->postJson('/admin/roles', [
                'branch_id' => $branch->id,
                'name' => 'Module Admin',
                'slug' => 'module_admin',
                'permission_slugs' => ['modules.manage'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('permission_slugs');

        $this->assertDatabaseMissing('roles', ['slug' => 'module_admin']);
    }
    public function test_branch_manager_cannot_assign_existing_role_with_module_permission(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $manageEmployees = Permission::query()->create([
            'name' => 'Manage employees',
            'slug' => 'employees.manage',
            'module' => 'Identity',
        ]);
        $manageModules = Permission::query()->create([
            'name' => 'Manage modules',
            'slug' => 'modules.manage',
            'module' => 'System',
        ]);

        $managerRole = Role::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Branch Manager',
            'slug' => 'branch_manager',
            'is_system' => true,
        ]);
        $managerRole->permissions()->attach($manageEmployees->id);

        $elevatedRole = Role::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Module Administrator',
            'slug' => 'module_administrator',
            'is_system' => false,
        ]);
        $elevatedRole->permissions()->attach($manageModules->id);

        $user = User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Manager',
            'phone' => '96562222222',
            'password' => 'manager-password-123',
            'status' => 'active',
        ]);
        $user->roles()->attach($managerRole->id);

        $token = Str::random(64);
        EmployeeSession::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'session_hash' => hash('sha256', $token),
            'logged_in_at' => now(),
            'last_activity_at' => now(),
        ]);

        $this->actingAs($user)->withSession(['employee_session_token' => $token])
            ->postJson('/admin/employees', [
                'name' => 'New Employee',
                'phone' => '96560001111',
                'password' => 'employee-password-123',
                'branch_id' => $branch->id,
                'role_ids' => [$elevatedRole->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role_ids');

        $this->assertDatabaseMissing('users', ['phone' => '96560001111']);
    }

}
