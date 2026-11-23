<?php

namespace App\Support;

use App\Models\User;
use App\Support\Modules\ModuleManager;

final class AdminHome
{
    /**
     * First admin screen the employee is allowed to open after login.
     */
    public static function pathFor(User $user): string
    {
        $moduleManager = app(ModuleManager::class);

        $candidates = [
            ['permission' => 'dashboard.view', 'module' => null, 'url' => '/admin/dashboard'],
            ['permission' => 'orders.view', 'module' => 'Orders', 'url' => '/admin/orders'],
            ['permission' => 'cashier.use', 'module' => 'Cashier', 'url' => '/admin/cashier'],
            ['permission' => 'catalog.view', 'module' => 'Catalog', 'url' => '/admin/products'],
            ['permission' => 'delivery.manage', 'module' => 'Delivery', 'url' => '/admin/delivery-areas'],
            ['permission' => 'employees.manage', 'module' => 'Identity', 'url' => '/admin/employees'],
            ['permission' => 'printing.manage', 'module' => 'Printing', 'url' => '/admin/printers'],
            ['permission' => 'reports.view', 'module' => 'Reporting', 'url' => '/admin/reports'],
            ['permission' => 'settings.manage', 'module' => 'SystemSettings', 'url' => '/admin/settings'],
            ['permission' => 'roles.manage', 'module' => 'AccessControl', 'url' => '/admin/roles'],
            ['permission' => 'branches.manage', 'module' => 'Branches', 'url' => '/admin/branches'],
            ['permission' => 'modules.manage', 'module' => 'SystemSettings', 'url' => '/admin/modules'],
        ];

        foreach ($candidates as $candidate) {
            if (! $user->hasPermissionTo($candidate['permission']) && ! $user->hasRole('system_admin')) {
                continue;
            }

            if ($candidate['module'] !== null && ! $moduleManager->enabled($candidate['module'])) {
                continue;
            }

            return $candidate['url'];
        }

        // Fallback keeps authenticated redirects out of a login loop even without permissions.
        return '/admin/dashboard';
    }
}
