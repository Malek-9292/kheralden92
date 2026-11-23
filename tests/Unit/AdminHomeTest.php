<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\AdminHome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_without_dashboard_lands_on_cashier_screen(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $permission = Permission::query()->create([
            'name' => 'استخدام الكاشير',
            'slug' => 'cashier.use',
            'module' => 'Cashier',
        ]);

        $role = Role::query()->create([
            'branch_id' => $branch->id,
            'name' => 'كاشير',
            'slug' => 'cashier',
        ]);
        $role->permissions()->attach($permission->id);

        $user = User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'كاشير',
            'phone' => '96560001111',
            'password' => 'cashier-password-12',
            'status' => 'active',
        ]);
        $user->roles()->attach($role->id);

        $this->assertSame('/admin/cashier', AdminHome::pathFor($user->fresh(['roles.permissions'])));
    }
}
