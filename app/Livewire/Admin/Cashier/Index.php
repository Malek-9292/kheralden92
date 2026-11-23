<?php

namespace App\Livewire\Admin\Cashier;

use App\Models\Order;
use App\Services\BranchAccess;
use App\Services\OrderWorkflow;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public string $fulfillmentFilter = '';

    public string $paymentFilter = 'unpaid';

    public int $pendingCount = 0;

    public int $lastSeenPendingCount = 0;

    public bool $soundMuted = false;

    public function mount(): void
    {
        $this->refreshPendingCount(initial: true);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFulfillmentFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentFilter(): void
    {
        $this->resetPage();
    }

    public function pollPending(): void
    {
        $this->refreshPendingCount();
    }

    public function toggleMute(): void
    {
        $this->soundMuted = ! $this->soundMuted;
    }

    public function acceptPending(int $orderId, OrderWorkflow $workflow): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('orders.accept'), 403);

        $order = Order::query()->findOrFail($orderId);
        app(BranchAccess::class)->assertCanAccess($user, $order->branch_id);

        if ($order->status !== 'pending') {
            session()->flash('status', 'الطلب لم يعد بانتظار القبول.');
            $this->refreshPendingCount(initial: true);

            return;
        }

        $workflow->advance($order, $user);
        session()->flash('status', 'تم قبول الطلب '.$order->order_number);
        $this->refreshPendingCount(initial: true);
    }

    public function collectCash(int $orderId, OrderWorkflow $workflow): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('cashier.use'), 403);
        $order = Order::query()->findOrFail($orderId);
        app(BranchAccess::class)->assertCanAccess($user, $order->branch_id);

        $workflow->collectCash($order, $user);
        session()->flash('status', 'تم تسجيل التحصيل النقدي للطلب '.$order->order_number);
    }

    public function render()
    {
        $user = auth()->user();
        $branchIds = app(BranchAccess::class)->visibleBranchIds($user);
        $query = Order::query()->whereIn('branch_id', $branchIds)
            ->whereIn('status', ['ready', 'out_for_delivery', 'completed'])
            ->with('items')->latest();

        if ($this->search !== '') {
            $term = trim($this->search);
            $query->where(function ($builder) use ($term): void {
                $builder->where('order_number', 'like', '%'.$term.'%')
                    ->orWhere('customer_name', 'like', '%'.$term.'%')
                    ->orWhere('customer_phone', 'like', '%'.$term.'%');
            });
        }

        if ($this->fulfillmentFilter !== '') {
            $query->where('fulfillment_type', $this->fulfillmentFilter);
        }

        if ($this->paymentFilter === 'unpaid') {
            $query->where('payment_status', 'unpaid');
        } elseif ($this->paymentFilter === 'paid') {
            $query->where('payment_status', 'paid');
        }

        $pendingOrders = Order::query()
            ->whereIn('branch_id', $branchIds)
            ->where('status', 'pending')
            ->with('items')
            ->latest()
            ->limit(8)
            ->get();

        return view('livewire.admin.cashier.index', [
            'orders' => $query->paginate(25),
            'pendingOrders' => $pendingOrders,
            'canAccept' => $user->hasPermissionTo('orders.accept'),
        ])->layout('layouts.admin', ['heading' => 'الكاشير والتحصيل']);
    }

    private function refreshPendingCount(bool $initial = false): void
    {
        $user = auth()->user();
        $branchIds = app(BranchAccess::class)->visibleBranchIds($user);
        $count = Order::query()
            ->whereIn('branch_id', $branchIds)
            ->where('status', 'pending')
            ->count();

        if (! $initial && ! $this->soundMuted && $count > $this->lastSeenPendingCount) {
            $this->dispatch('cashier-new-order-beep');
        }

        $this->pendingCount = $count;
        $this->lastSeenPendingCount = $count;
    }
}
