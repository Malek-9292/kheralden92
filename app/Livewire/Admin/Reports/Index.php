<?php

namespace App\Livewire\Admin\Reports;

use App\Models\Branch;
use App\Models\Order;
use App\Services\BranchAccess;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        // Timestamps are stored in APP_TIMEZONE (Asia/Kuwait) wall-clock — do not
        // convert the filter window to UTC or evening orders fall outside "today".
        $from = Carbon::parse($data['dateFrom'], 'Asia/Kuwait')->startOfDay();
        $to = Carbon::parse($data['dateTo'], 'Asia/Kuwait')->endOfDay();

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

        $dayExpression = 'DATE(paid_at)';

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

    public function exportCsv(): StreamedResponse
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('reports.view'), 403);

        $data = $this->validate([
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date', 'after_or_equal:dateFrom'],
            'fulfillmentFilter' => ['nullable', 'in:pickup,delivery'],
        ]);

        $branchIds = app(BranchAccess::class)->visibleBranchIds($user);
        if ($user->hasRole('system_admin') && $this->branchId) {
            abort_unless($branchIds->contains((int) $this->branchId), 403);
            $branchIds = collect([(int) $this->branchId]);
        }

        $from = Carbon::parse($data['dateFrom'], 'Asia/Kuwait')->startOfDay();
        $to = Carbon::parse($data['dateTo'], 'Asia/Kuwait')->endOfDay();

        $query = Order::query()
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('created_at', [$from, $to])
            ->with('items')
            ->orderBy('id');

        if ($this->fulfillmentFilter !== '') {
            $query->where('fulfillment_type', $this->fulfillmentFilter);
        }

        $filename = 'fatoosh-orders-'.$data['dateFrom'].'_'.$data['dateTo'].'.csv';

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM for Excel Arabic
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'order_number',
                'created_at_kuwait',
                'customer_name',
                'customer_phone',
                'fulfillment_type',
                'status',
                'payment_status',
                'subtotal',
                'delivery_fee',
                'total',
                'items',
            ]);

            $query->chunkById(100, function ($orders) use ($out): void {
                foreach ($orders as $order) {
                    fputcsv($out, [
                        $order->order_number,
                        optional($order->created_at)->timezone('Asia/Kuwait')->format('Y-m-d H:i:s'),
                        $order->customer_name,
                        $order->customer_phone,
                        $order->fulfillment_type,
                        $order->status,
                        $order->payment_status,
                        number_format((float) $order->subtotal, 3, '.', ''),
                        number_format((float) $order->delivery_fee, 3, '.', ''),
                        number_format((float) $order->total, 3, '.', ''),
                        $order->items->map(fn ($item) => $item->product_name.'×'.$item->quantity)->implode(' | '),
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
