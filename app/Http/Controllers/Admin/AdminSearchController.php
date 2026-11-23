<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\DeliveryArea;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\BranchAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSearchController extends Controller
{
    public function __invoke(Request $request, BranchAccess $branchAccess): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $term = trim($data['q']);
        $user = $request->user();
        $branchIds = $branchAccess->visibleBranchIds($user);
        $results = [];

        $navigation = [
            ['permission' => 'dashboard.view', 'label' => 'لوحة التحكم', 'url' => '/admin/dashboard'],
            ['permission' => 'orders.view', 'label' => 'الطلبات', 'url' => '/admin/orders'],
            ['permission' => 'catalog.view', 'label' => 'الأقسام والأصناف', 'url' => '/admin/catalog'],
            ['permission' => 'delivery.manage', 'label' => 'مناطق التوصيل', 'url' => '/admin/delivery-areas'],
            ['permission' => 'employees.manage', 'label' => 'الموظفون', 'url' => '/admin/employees'],
            ['permission' => 'branches.manage', 'label' => 'الفروع', 'url' => '/admin/branches'],
            ['permission' => 'roles.manage', 'label' => 'الأدوار والصلاحيات', 'url' => '/admin/roles'],
            ['permission' => 'cashier.use', 'label' => 'الكاشير', 'url' => '/admin/cashier'],
            ['permission' => 'printing.manage', 'label' => 'الطابعات', 'url' => '/admin/printers'],
        ];

        foreach ($navigation as $item) {
            if ($user->hasPermissionTo($item['permission']) || $user->hasRole('system_admin')) {
                if (mb_stripos($item['label'], $term) !== false) {
                    $results['sections'][] = [
                        'title' => $item['label'],
                        'url' => $item['url'],
                        'type' => 'section',
                    ];
                }
            }
        }

        if ($user->hasPermissionTo('orders.view') || $user->hasRole('system_admin')) {
            $orders = Order::query()
                ->whereIn('branch_id', $branchIds)
                ->where(function ($query) use ($term): void {
                    $query->where('order_number', 'like', "%{$term}%")
                        ->orWhere('customer_name', 'like', "%{$term}%")
                        ->orWhere('customer_phone', 'like', "%{$term}%");
                })
                ->latest()
                ->limit(5)
                ->get(['id', 'order_number', 'customer_name', 'customer_phone', 'status']);

            $results['orders'] = $orders->map(fn (Order $order) => [
                'title' => $order->order_number,
                'subtitle' => $order->customer_name.' · '.$order->customer_phone.' · '.$order->status,
                'url' => '/admin/orders?search='.urlencode($order->order_number),
                'type' => 'order',
            ])->values();
        }

        if ($user->hasPermissionTo('catalog.view') || $user->hasRole('system_admin')) {
            $results['products'] = Product::query()
                ->whereIn('branch_id', $branchIds)
                ->where('name', 'like', "%{$term}%")
                ->orderBy('name')
                ->limit(5)
                ->get(['id', 'name', 'price', 'branch_id'])
                ->map(fn (Product $product) => [
                    'title' => $product->name,
                    'subtitle' => 'صنف · '.$product->price.' د.ك',
                    'url' => '/admin/products?search='.urlencode($product->name),
                    'type' => 'product',
                ])->values();

            $results['categories'] = Category::query()
                ->whereIn('branch_id', $branchIds)
                ->where('name', 'like', "%{$term}%")
                ->orderBy('name')
                ->limit(5)
                ->get(['id', 'name'])
                ->map(fn (Category $category) => [
                    'title' => $category->name,
                    'subtitle' => 'قسم',
                    'url' => '/admin/categories?search='.urlencode($category->name),
                    'type' => 'category',
                ])->values();
        }

        if ($user->hasPermissionTo('employees.manage') || $user->hasRole('system_admin')) {
            $results['employees'] = User::query()
                ->whereIn('branch_id', $branchIds)
                ->where(function ($query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                })
                ->orderBy('name')
                ->limit(5)
                ->get(['id', 'name', 'phone', 'status'])
                ->map(fn (User $employee) => [
                    'title' => $employee->name,
                    'subtitle' => $employee->phone.' · '.$employee->status,
                    'url' => '/admin/employees',
                    'type' => 'employee',
                ])->values();
        }

        if ($user->hasPermissionTo('delivery.manage') || $user->hasRole('system_admin')) {
            $results['delivery_areas'] = DeliveryArea::query()
                ->whereIn('branch_id', $branchIds)
                ->where('name', 'like', "%{$term}%")
                ->orderBy('name')
                ->limit(5)
                ->get(['id', 'name', 'governorate'])
                ->map(fn (DeliveryArea $area) => [
                    'title' => $area->name,
                    'subtitle' => $area->governorate.' · منطقة توصيل',
                    'url' => '/admin/delivery-areas',
                    'type' => 'delivery_area',
                ])->values();
        }

        if ($user->hasPermissionTo('branches.manage') || $user->hasRole('system_admin')) {
            $results['branches'] = Branch::query()
                ->where(function ($query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                })
                ->orderBy('name')
                ->limit(5)
                ->get(['id', 'name', 'code'])
                ->map(fn (Branch $branch) => [
                    'title' => $branch->name,
                    'subtitle' => $branch->code,
                    'url' => '/admin/branches',
                    'type' => 'branch',
                ])->values();
        }

        return response()->json([
            'query' => $term,
            'results' => $results,
        ]);
    }
}
