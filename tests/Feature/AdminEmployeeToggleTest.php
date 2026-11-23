<?php

namespace Tests\Feature;

use App\Livewire\Admin\Employees\Index;
use App\Models\Branch;
use App\Models\EmployeeSession;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AdminEmployeeToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_suspend_and_reactivate_employee_from_list(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $permission = Permission::query()->create([
            'name' => 'Manage employees',
            'slug' => 'employees.manage',
            'module' => 'Identity',
        ]);

        $managerRole = Role::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Branch Manager',
            'slug' => 'branch_manager',
            'is_system' => true,
        ]);
        $managerRole->permissions()->attach($permission->id);

        $cashierRole = Role::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Cashier',
            'slug' => 'cashier',
            'is_system' => true,
        ]);

        $manager = User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Manager',
            'phone' => '96561110000',
            'password' => 'manager-password-12',
            'status' => 'active',
        ]);
        $manager->roles()->attach($managerRole->id);

        $cashier = User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Cashier',
            'phone' => '96561110001',
            'password' => 'cashier-password-12',
            'status' => 'active',
        ]);
        $cashier->roles()->attach($cashierRole->id);

        $token = Str::random(64);
        EmployeeSession::query()->create([
            'user_id' => $manager->id,
            'branch_id' => $branch->id,
            'session_hash' => hash('sha256', $token),
            'logged_in_at' => now(),
            'last_activity_at' => now(),
        ]);

        $cashierSession = EmployeeSession::query()->create([
            'user_id' => $cashier->id,
            'branch_id' => $branch->id,
            'session_hash' => hash('sha256', Str::random(64)),
            'logged_in_at' => now(),
            'last_activity_at' => now(),
        ]);

        $this->actingAs($manager)->withSession(['employee_session_token' => $token]);

        Livewire::test(Index::class)
            ->call('toggleStatus', $cashier->id)
            ->assertHasNoErrors();

        $this->assertSame('suspended', $cashier->fresh()->status);
        $this->assertNotNull($cashierSession->fresh()->logged_out_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'employee.status_updated',
            'auditable_id' => $cashier->id,
        ]);

        Livewire::test(Index::class)
            ->call('toggleStatus', $cashier->id)
            ->assertHasNoErrors();

        $this->assertSame('active', $cashier->fresh()->status);
    }
}
