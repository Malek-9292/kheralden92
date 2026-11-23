<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_only_use_permissions_from_global_or_own_branch_roles(): void
    {
        $branchId = DB::table('branches')->insertGetId([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherBranchId = DB::table('branches')->insertGetId([
            'code' => 'OTHER',
            'name' => 'فرع آخر',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::create([
            'branch_id' => $branchId,
            'name' => 'Cashier Test',
            'phone' => '96500000001',
            'password' => 'test-password',
            'status' => 'active',
        ]);

        $permission = Permission::create([
            'name' => 'Accept orders',
            'slug' => 'orders.accept',
            'module' => 'Orders',
        ]);

        $ownBranchRole = Role::create([
            'branch_id' => $branchId,
            'name' => 'Cashier',
            'slug' => 'cashier',
            'is_system' => true,
        ]);
        $ownBranchRole->permissions()->attach($permission->id);
        $user->roles()->attach($ownBranchRole->id);

        $otherBranchRole = Role::create([
            'branch_id' => $otherBranchId,
            'name' => 'Other Branch Admin',
            'slug' => 'other-branch-admin',
            'is_system' => true,
        ]);
        $otherBranchRole->permissions()->attach($permission->id);

        self::assertTrue($user->hasRole('cashier'));
        self::assertTrue($user->hasPermissionTo('orders.accept'));
        $user->roles()->detach($ownBranchRole->id);
        $user->roles()->attach($otherBranchRole->id);
        self::assertFalse($user->hasPermissionTo('orders.accept'));
    }
}
