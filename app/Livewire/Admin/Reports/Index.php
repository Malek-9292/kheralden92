<?php

namespace App\Livewire\Admin\Reports;

use App\Models\Branch;
use App\Models\Order;
use App\Services\BranchAccess;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public ?int $branchId = null;

    public string $fulfillmentFilter = '';

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('reports.view'), 403);
        $this->dateFrom = now('Asia/Kuwait')->startOfMonth()->toDateString();
        $this->dateTo = now('Asia/Kuwait')->toDateString();
        $this->branchId = $user->hasRole('system_admin') ? null : $user->branch_id;
    }

    public function render()
    {
        $user = auth()->user();
        $branchIds = app(BranchAccess::class)->visibleBranchIds($user);
        $data = $this->validate([
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date', 'after_or_equal:dateFrom'],
            'fulfillmentFilter' => ['nullable', 'in:pickup,delivery'],
        ]);

        if ($user->hasRole('system_admin') && $this->branchId) {
            abort_unless($branchIds->contains((int) $this->branchId), 403);
            $branchIds = collect([(int) $this->branchId]);
        }

        $from = Carbon::parse($data['dateFrom'], 'Asia/Kuwait')->startOfDay()->utc();
        $to = Carbon::parse($data['dateTo'], 'Asia/Kuwait')->endOfDay()->utc();

        $base = Order::query()->whereIn('branch_id', $branchIds)->whereBetween('created_at', [$from, $to]);
        if ($this->fulfillmentFilter !== '') {
            $base->where('fulfillment_type', $this->fulfillmentFilter);
        }

        $totalOrders = (clone $base)->count();
        $cancelledOrders = (clone $base)->where('status', 'cancelled')->count();
        $unpaidOpenOrders = (clone $base)
            ->where('payment_status', 'unpaid')
            ->whereNotIn('status', ['cancelled'])
            ->count();

        $paidOrders = Order::query()
            ->whereIn('branch_id', $branchIds)
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$from, $to]);
        if ($this->fulfillmentFilter !== '') {
            $paidOrders->where('fulfillment_type', $this->fulfillmentFilter);
        }

        $paidCount = (clone $paidOrders)->count();
        $paidRevenue = (clone $paidOrders)->sum('total');
        $averageTicket = $paidCount > 0 ? (float) $paidRevenue / $paidCount : 0;

        $statusCounts = (clone $base)->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')->pluck('total', 'status');

        $dayExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "DATE(datetime(paid_at, '+3 hours'))"
            : 'DATE(DATE_ADD(paid_at, INTERVAL 3 HOUR))';

        $dailySales = (clone $paidOrders)
            ->selectRaw("{$dayExpression} as day, SUM(total) as total")
            ->groupBy('day')->orderBy('day')->get();

        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.branch_id', $branchIds)
            ->whereBetween('orders.created_at', [$from, $to])
            ->where('orders.status', '!=', 'cancelled')
            ->when($this->fulfillmentFilter !== '', fn ($query) => $query->where('orders.fulfillment_type', $this->fulfillmentFilter))
            ->select('order_items.product_name', 'order_items.category_name')
            ->selectRaw('SUM(order_items.quantity) as quantity, SUM(order_items.line_total) as sales')
            ->groupBy('order_items.product_name', 'order_items.category_name')
            ->orderByDesc('quantity')
            ->limit(10)
            ->get();

        return view('livewire.admin.reports.index', [
            'totalOrders' => $totalOrders,
            'cancelledOrders' => $cancelledOrders,
            'unpaidOpenOrders' => $unpaidOpenOrders,
            'paidCount' => $paidCount,
            'paidRevenue' => $paidRevenue,
            'averageTicket' => $averageTicket,
            'statusCounts' => $statusCounts,
            'dailySales' => $dailySales,
            'topProducts' => $topProducts,
            'branches' => $user->hasRole('system_admin') ? Branch::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'isSystemAdmin' => $user->hasRole('system_admin'),
        ])->layout('layouts.admin', ['heading' => 'التقارير']);
    }
}
