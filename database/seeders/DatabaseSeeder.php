<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->firstOrCreate(
            ['code' => 'KHAL'],
            ['name' => 'فتوش الخالدية', 'timezone' => 'Asia/Kuwait', 'is_active' => true],
        );

        $permissions = [
            ['name' => 'View dashboard', 'slug' => 'dashboard.view', 'module' => 'System'],
            ['name' => 'Manage employees', 'slug' => 'employees.manage', 'module' => 'Identity'],
            ['name' => 'Manage branches', 'slug' => 'branches.manage', 'module' => 'Branches'],
            ['name' => 'Manage roles', 'slug' => 'roles.manage', 'module' => 'AccessControl'],
            ['name' => 'View catalog', 'slug' => 'catalog.view', 'module' => 'Catalog'],
            ['name' => 'Manage catalog', 'slug' => 'catalog.manage', 'module' => 'Catalog'],
            ['name' => 'View orders', 'slug' => 'orders.view', 'module' => 'Orders'],
            ['name' => 'Accept orders', 'slug' => 'orders.accept', 'module' => 'Orders'],
            ['name' => 'Cancel orders', 'slug' => 'orders.cancel', 'module' => 'Orders'],
            ['name' => 'Use cashier', 'slug' => 'cashier.use', 'module' => 'Cashier'],
            ['name' => 'Manage printers', 'slug' => 'printing.manage', 'module' => 'Printing'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['slug' => $permission['slug']], $permission);
        }

        $systemAdmin = Role::query()->firstOrCreate(
            ['branch_id' => null, 'slug' => 'system_admin'],
            ['name' => 'مدير النظام الرئيسي', 'guard_name' => 'web', 'is_system' => true],
        );
        $systemAdmin->permissions()->sync(Permission::query()->pluck('id'));

        $branchManager = Role::query()->firstOrCreate(
            ['branch_id' => $branch->id, 'slug' => 'branch_manager'],
            ['name' => 'مدير الفرع', 'guard_name' => 'web', 'is_system' => true],
        );
        $branchManager->permissions()->sync(
            Permission::query()->where('slug', '!=', 'branches.manage')->pluck('id'),
        );
    }
}
