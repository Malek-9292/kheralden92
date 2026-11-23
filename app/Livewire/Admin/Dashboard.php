<?php

namespace App\Livewire\Admin;

use App\Models\DeliveryArea;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\BranchAccess;
use App\Support\Modules\ModuleManager;
use Carbon\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('dashboard.view'), 403);

        $branchIds = app(BranchAccess::class)->visibleBranchIds($user);
        $moduleManager = app(ModuleManager::class);
        $todayStart = Carbon::now('Asia/Kuwait')->startOfDay()->utc();
        $todayEnd = Carbon::now('Asia/Kuwait')->endOfDay()->utc();

        $stats = [
            'pending_orders' => $moduleManager->enabled('Orders')
                ? Order::query()->whereIn('branch_id', $branchIds)->where('status', 'pending')->count()
                : null,
            'today_orders' => $moduleManager->enabled('Orders')
                ? Order::query()->whereIn('branch_id', $branchIds)->whereBetween('created_at', [$todayStart, $todayEnd])->count()
                : null,
            'active_products' => $moduleManager->enabled('Catalog')
                ? Product::query()->whereIn('branch_id', $branchIds)->where('is_active', true)->count()
                : null,
            'employees' => ($user->hasPermissionTo('employees.manage') && $moduleManager->enabled('Identity'))
                ? User::query()->whereIn('branch_id', $branchIds)->where('status', 'active')->count()
                : null,
            'delivery_areas' => ($user->hasPermissionTo('delivery.manage') && $moduleManager->enabled('Delivery'))
                ? DeliveryArea::query()->whereIn('branch_id', $branchIds)->where('is_active', true)->count()
                : null,
        ];

        $recentOrders = ($user->hasPermissionTo('orders.view') && $moduleManager->enabled('Orders'))
            ? Order::query()->whereIn('branch_id', $branchIds)->with('items')->latest()->limit(8)->get()
            : collect();

        $quickLinks = collect([
            ['permission' => 'orders.view', 'module' => 'Orders', 'label' => 'الطلبات', 'hint' => 'متابعة الحالات والتحصيل', 'url' => '/admin/orders'],
            ['permission' => 'catalog.view', 'module' => 'Catalog', 'label' => 'الأصناف', 'hint' => 'الأسعار والصور والتوفر', 'url' => '/admin/products'],
            ['permission' => 'employees.manage', 'module' => 'Identity', 'label' => 'الموظفون', 'hint' => 'الحسابات والأدوار', 'url' => '/admin/employees'],
            ['permission' => 'roles.manage', 'module' => 'AccessControl', 'label' => 'الأدوار', 'hint' => 'الصلاحيات حسب الفرع', 'url' => '/admin/roles'],
            ['permission' => 'branches.manage', 'module' => 'Branches', 'label' => 'الفروع', 'hint' => 'إعداد الفروع النشطة', 'url' => '/admin/branches'],
            ['permission' => 'cashier.use', 'module' => 'Cashier', 'label' => 'الكاشير', 'hint' => 'قبول وتحصيل الطلبات', 'url' => '/admin/cashier'],
            ['permission' => 'delivery.manage', 'module' => 'Delivery', 'label' => 'مناطق التوصيل', 'hint' => 'الرسوم والحد الأدنى', 'url' => '/admin/delivery-areas'],
            ['permission' => 'reports.view', 'module' => 'Reporting', 'label' => 'التقارير', 'hint' => 'ملخص التشغيل اليومي', 'url' => '/admin/reports'],
        ])->filter(function (array $link) use ($user, $moduleManager): bool {
            return $user->hasPermissionTo($link['permission']) && $moduleManager->enabled($link['module']);
        })->values();

        return view('livewire.admin.dashboard', compact('stats', 'recentOrders', 'quickLinks'))
            ->layout('layouts.admin', ['heading' => 'لوحة التحكم']);
    }
}
