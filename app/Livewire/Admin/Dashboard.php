<?php

namespace App\Livewire\Admin;

use App\Models\DeliveryArea;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\BranchAccess;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(BranchAccess $branchAccess)
    {
        $user = auth()->user();
        $branchIds = $branchAccess->visibleBranchIds($user);

        $stats = [
            'pending_orders' => Order::query()->whereIn('branch_id', $branchIds)->where('status', 'pending')->count(),
            'active_products' => Product::query()->whereIn('branch_id', $branchIds)->where('is_active', true)->count(),
            'employees' => $user->hasPermissionTo('employees.manage')
                ? User::query()->whereIn('branch_id', $branchIds)->where('status', 'active')->count()
                : null,
            'delivery_areas' => $user->hasPermissionTo('delivery.manage')
                ? DeliveryArea::query()->whereIn('branch_id', $branchIds)->where('is_active', true)->count()
                : null,
        ];

        $recentOrders = $user->hasPermissionTo('orders.view')
            ? Order::query()->whereIn('branch_id', $branchIds)->with('items')->latest()->limit(8)->get()
            : collect();

        return view('livewire.admin.dashboard', compact('stats', 'recentOrders'))
            ->layout('layouts.admin');
    }
}
