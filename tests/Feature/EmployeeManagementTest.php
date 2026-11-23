<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_manager_can_create_employee_and_action_is_audited(): void
    {
        $branch = Branch::create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'Manage employees',
            'slug' => 'employees.manage',
            'module' => 'Identity',
        ]);

        $managerRole = Role::create([
            'branch_id' => $branch->id,
            'name' => 'Branch Manager',
            'slug' => 'branch_manager',
            'is_system' => true,
        ]);
        $managerRole->permissions()->attach($permission->id);

        $cashierRole = Role::create([
            'branch_id' => $branch->id,
            'name' => 'Cashier',
            'slug' => 'cashier',
            'is_system' => true,
        ]);

        $manager = User::create([
            'branch_id' => $branch->id,
            'name' => 'Branch Manager',
            'phone' => '96560000000',
            'password' => 'manager-password-123',
            'status' => 'active',
        ]);
        $manager->roles()->attach($managerRole->id);

        $this->postJson('/admin/session', [
            'phone' => '96560000000',
            'password' => 'manager-password-123',
        ])->assertOk();

        $response = $this->postJson('/admin/employees', [
            'name' => 'New Cashier',
            'phone' => '96560000001',
            'password' => 'cashier-password-123',
            'branch_id' => $branch->id,
            'role_ids' => [$cashierRole->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('employee.phone', '96560000001')
            ->assertJsonPath('employee.roles.0.slug', 'cashier');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'employee.created',
            'actor_id' => $manager->id,
        ]);
    }

    public function test_branch_manager_cannot_create_employee_in_another_branch(): void
    {
        $branch = Branch::create(['code' => 'A', 'name' => 'Branch A', 'timezone' => 'Asia/Kuwait']);
        $otherBranch = Branch::create(['code' => 'B', 'name' => 'Branch B', 'timezone' => 'Asia/Kuwait']);

        $permission = Permission::create([
            'name' => 'Manage employees',
            'slug' => 'employees.manage',
            'module' => 'Identity',
        ]);
        $role = Role::create([
            'branch_id' => $branch->id,
            'name' => 'Branch Manager',
            'slug' => 'branch_manager',
            'is_system' => true,
        ]);
        $role->permissions()->attach($permission->id);

        $manager = User::create([
            'branch_id' => $branch->id,
            'name' => 'Manager',
            'phone' => '96560000002',
            'password' => 'manager-password-123',
            'status' => 'active',
        ]);
        $manager->roles()->attach($role->id);

        $this->postJson('/admin/session', [
            'phone' => '96560000002',
            'password' => 'manager-password-123',
        ])->assertOk();

        $this->postJson('/admin/employees', [
            'name' => 'Other Branch Cashier',
            'phone' => '96560000003',
            'password' => 'cashier-password-123',
            'branch_id' => $otherBranch->id,
        ])->assertForbidden();
    }

    public function test_created_employee_can_sign_in_with_assigned_password(): void
    {
        $branch = Branch::create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'Manage employees',
            'slug' => 'employees.manage',
            'module' => 'Identity',
        ]);

        $managerRole = Role::create([
            'branch_id' => $branch->id,
            'name' => 'Branch Manager',
            'slug' => 'branch_manager',
            'is_system' => true,
        ]);
        $managerRole->permissions()->attach($permission->id);

        $cashierRole = Role::create([
            'branch_id' => $branch->id,
            'name' => 'Cashier',
            'slug' => 'cashier',
            'is_system' => true,
        ]);

        $manager = User::create([
            'branch_id' => $branch->id,
            'name' => 'Branch Manager',
            'phone' => '96560000010',
            'password' => 'manager-password-123',
            'status' => 'active',
        ]);
        $manager->roles()->attach($managerRole->id);

        $this->postJson('/admin/session', [
            'phone' => '96560000010',
            'password' => 'manager-password-123',
        ])->assertOk();

        $this->postJson('/admin/employees', [
            'name' => 'Login Cashier',
            'phone' => '96560000011',
            'password' => 'cashier-password-123',
            'branch_id' => $branch->id,
            'role_ids' => [$cashierRole->id],
        ])->assertCreated();

        $this->deleteJson('/admin/session')->assertOk();

        $this->postJson('/admin/session', [
            'phone' => '96560000011',
            'password' => 'cashier-password-123',
        ])->assertOk()->assertJsonPath('user.phone', '96560000011');
    }

    public function test_password_change_revokes_existing_employee_sessions(): void
    {
        $branch = Branch::create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'Manage employees',
            'slug' => 'employees.manage',
            'module' => 'Identity',
        ]);

        $managerRole = Role::create([
            'branch_id' => $branch->id,
            'name' => 'Branch Manager',
            'slug' => 'branch_manager',
            'is_system' => true,
        ]);
        $managerRole->permissions()->attach($permission->id);

        $cashierRole = Role::create([
            'branch_id' => $branch->id,
            'name' => 'Cashier',
            'slug' => 'cashier',
            'is_system' => true,
        ]);

        $manager = User::create([
            'branch_id' => $branch->id,
            'name' => 'Branch Manager',
            'phone' => '96560000020',
            'password' => 'manager-password-123',
            'status' => 'active',
        ]);
        $manager->roles()->attach($managerRole->id);

        $cashier = User::create([
            'branch_id' => $branch->id,
            'name' => 'Cashier',
            'phone' => '96560000021',
            'password' => 'cashier-password-123',
            'status' => 'active',
        ]);
        $cashier->roles()->attach($cashierRole->id);

        $this->postJson('/admin/session', [
            'phone' => '96560000021',
            'password' => 'cashier-password-123',
        ])->assertOk();

        $this->assertDatabaseHas('employee_sessions', [
            'user_id' => $cashier->id,
            'logged_out_at' => null,
        ]);

        $this->deleteJson('/admin/session')->assertOk();

        $this->postJson('/admin/session', [
            'phone' => '96560000020',
            'password' => 'manager-password-123',
        ])->assertOk();

        $this->patchJson('/admin/employees/'.$cashier->id, [
            'password' => 'cashier-password-999',
        ])->assertOk();

        $this->assertDatabaseMissing('employee_sessions', [
            'user_id' => $cashier->id,
            'logged_out_at' => null,
        ]);

        $this->deleteJson('/admin/session')->assertOk();

        $this->postJson('/admin/session', [
            'phone' => '96560000021',
            'password' => 'cashier-password-999',
        ])->assertOk();
    }
}
