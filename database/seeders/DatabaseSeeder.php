<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
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

        $initialCategories = [
            ['name' => 'أفطار الخالدية', 'slug' => 'eftar-alkhaldiya'],
            ['name' => 'العصائر', 'slug' => 'juices'],
            ['name' => 'مشويات الخالدية', 'slug' => 'grills'],
            ['name' => 'صواني مشويات', 'slug' => 'grill-trays'],
            ['name' => 'مواعين الخالدية', 'slug' => 'plates'],
            ['name' => 'الساندويتشات الخفيفة', 'slug' => 'light-sandwiches'],
            ['name' => 'الساندويتشات الحارة والباردة', 'slug' => 'hot-cold-sandwiches'],
            ['name' => 'ساندويتشات الصاج', 'slug' => 'saj-sandwiches'],
        ];

        foreach ($initialCategories as $index => $category) {
            Category::query()->firstOrCreate(
                ['branch_id' => $branch->id, 'slug' => $category['slug']],
                ['name' => $category['name'], 'sort_order' => $index, 'is_active' => true],
            );
        }

        $permissions = [
            ['name' => 'View dashboard', 'slug' => 'dashboard.view', 'module' => 'System'],
            ['name' => 'Manage employees', 'slug' => 'employees.manage', 'module' => 'Identity'],
            ['name' => 'Manage branches', 'slug' => 'branches.manage', 'module' => 'Branches'],
            ['name' => 'Manage roles', 'slug' => 'roles.manage', 'module' => 'AccessControl'],
            ['name' => 'View catalog', 'slug' => 'catalog.view', 'module' => 'Catalog'],
            ['name' => 'Manage catalog', 'slug' => 'catalog.manage', 'module' => 'Catalog'],
            ['name' => 'Manage delivery areas', 'slug' => 'delivery.manage', 'module' => 'Delivery'],
            ['name' => 'Manage restaurant settings', 'slug' => 'settings.manage', 'module' => 'Settings'],
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

        $templates = [
            'operations_manager' => [
                'name' => 'مدير تشغيل',
                'permissions' => ['dashboard.view', 'employees.manage', 'roles.manage', 'catalog.view', 'catalog.manage', 'delivery.manage', 'settings.manage', 'orders.view', 'orders.accept', 'orders.cancel', 'cashier.use', 'printing.manage'],
            ],
            'content_manager' => [
                'name' => 'مدير محتوى',
                'permissions' => ['dashboard.view', 'catalog.view', 'catalog.manage', 'settings.manage'],
            ],
            'cashier' => [
                'name' => 'كاشير',
                'permissions' => ['catalog.view', 'orders.view', 'orders.accept', 'orders.cancel', 'cashier.use'],
            ],
            'shift_supervisor' => [
                'name' => 'مشرف وردية',
                'permissions' => ['dashboard.view', 'employees.manage', 'catalog.view', 'orders.view', 'orders.accept', 'orders.cancel', 'cashier.use'],
            ],
        ];

        foreach ($templates as $slug => $template) {
            $role = Role::query()->firstOrCreate(
                ['branch_id' => $branch->id, 'slug' => $slug],
                ['name' => $template['name'], 'guard_name' => 'web', 'is_system' => true],
            );
            $role->permissions()->sync(
                Permission::query()->whereIn('slug', $template['permissions'])->pluck('id'),
            );
        }
    }
}
