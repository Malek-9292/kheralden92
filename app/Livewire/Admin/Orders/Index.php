<?php

namespace App\Livewire\Admin\Orders;

use App\Models\AuditLog;
use App\Models\Order;
use App\Services\BranchAccess;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $fulfillmentFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedFulfillmentFilter(): void
    {
        $this->resetPage();
    }

    public function advance(int $orderId): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('orders.accept'), 403);

        $order = Order::query()->findOrFail($orderId);
        app(BranchAccess::class)->assertCanAccess($user, $order->branch_id);

        $next = match (true) {
            $order->status === 'pending' => 'confirmed',
            $order->status === 'confirmed' => 'preparing',
            $order->status === 'preparing' => 'ready',
            $order->status === 'ready' && $order->fulfillment_type === 'delivery' => 'out_for_delivery',
            $order->status === 'ready' => 'completed',
            $order->status === 'out_for_delivery' => 'completed',
            default => null,
        };

        abort_if($next === null, 422, 'لا توجد خطوة تالية متاحة لهذا الطلب.');

        $before = $order->status;

        DB::transaction(function () use ($order, $next, $before, $user): void {
            $order->status = $next;

            if ($next === 'confirmed') {
                $order->confirmed_at = now();
            }

            if ($next === 'completed') {
                $order->completed_at = now();
            }

            $order->save();

            AuditLog::query()->create([
                'branch_id' => $order->branch_id,
                'actor_id' => $user->id,
                'action' => 'order.status_updated',
                'auditable_type' => Order::class,
                'auditable_id' => $order->id,
                'before_values' => ['status' => $before],
                'after_values' => ['status' => $next],
                'created_at' => now(),
            ]);
        });
    }

    public function cancel(int $orderId): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('orders.cancel'), 403);

        $order = Order::query()->findOrFail($orderId);
        app(BranchAccess::class)->assertCanAccess($user, $order->branch_id);
        abort_if(in_array($order->status, ['completed', 'cancelled'], true), 422);

        $before = $order->status;

        DB::transaction(function () use ($order, $before, $user): void {
            $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            AuditLog::query()->create([
                'branch_id' => $order->branch_id,
                'actor_id' => $user->id,
                'action' => 'order.cancelled',
                'auditable_type' => Order::class,
                'auditable_id' => $order->id,
                'before_values' => ['status' => $before],
                'after_values' => ['status' => 'cancelled'],
                'reason' => 'إلغاء من لوحة الطلبات.',
                'created_at' => now(),
            ]);
        });
    }

    public function collectCash(int $orderId): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('cashier.use'), 403);

        $order = Order::query()->findOrFail($orderId);
        app(BranchAccess::class)->assertCanAccess($user, $order->branch_id);

        $eligible = $order->fulfillment_type === 'pickup'
            ? ['ready', 'completed']
            : ['out_for_delivery', 'completed'];

        abort_if($order->payment_status === 'paid' || ! in_array($order->status, $eligible, true), 422);

        DB::transaction(function () use ($order, $user): void {
            $order->update([
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            AuditLog::query()->create([
                'branch_id' => $order->branch_id,
                'actor_id' => $user->id,
                'action' => 'order.payment_collected',
                'auditable_type' => Order::class,
                'auditable_id' => $order->id,
                'before_values' => ['payment_status' => 'unpaid'],
                'after_values' => ['payment_status' => 'paid', 'payment_method' => 'cash'],
                'created_at' => now(),
            ]);
        });
    }

    public function render()
    {
        $user = auth()->user();
        $branchIds = app(BranchAccess::class)->visibleBranchIds($user);

        $query = Order::query()
            ->whereIn('branch_id', $branchIds)
            ->with('items')
            ->latest();

        if ($this->search !== '') {
            $term = trim($this->search);
            $query->where(function ($builder) use ($term): void {
                $builder->where('order_number', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_phone', 'like', "%{$term}%");
            });
        }

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->fulfillmentFilter !== '') {
            $query->where('fulfillment_type', $this->fulfillmentFilter);
        }

        return view('livewire.admin.orders.index', [
            'orders' => $query->paginate(20),
            'canAdvance' => $user->hasPermissionTo('orders.accept'),
            'canCancel' => $user->hasPermissionTo('orders.cancel'),
            'canCollect' => $user->hasPermissionTo('cashier.use'),
        ])->layout('layouts.admin', ['heading' => 'إدارة الطلبات']);
    }
}
