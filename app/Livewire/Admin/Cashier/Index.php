<?php

namespace App\Livewire\Admin\Cashier;

use App\Models\Order;
use App\Services\BranchAccess;
use App\Services\OrderWorkflow;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    /** Active POS queue: new storefront orders through collection-ready / unpaid completed. */
    public const QUEUE_STATUSES = [
        'pending',
        'confirmed',
        'preparing',
        'ready',
        'out_for_delivery',
        'completed',
    ];

    #[Url]
    public string $search = '';

    public string $fulfillmentFilter = '';

    public string $statusFilter = '';

    public string $paymentFilter = 'unpaid';

    public int $pendingCount = 0;

    public int $lastSeenPendingCount = 0;

    public int $queueCount = 0;

    public int $lastSeenQueueCount = 0;

    public bool $soundMuted = false;

    public function mount(): void
    {
        $this->refreshQueueSignals(initial: true);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFulfillmentFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentFilter(): void
    {
        $this->resetPage();
    }

    public function pollPending(): void
    {
        // Re-renders the POS queue so cash checkout (auto-confirmed) orders appear promptly.
        $this->refreshQueueSignals();
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
            $this->refreshQueueSignals(initial: true);

            return;
        }

        $workflow->advance($order, $user);
        session()->flash('status', 'تم قبول الطلب '.$order->order_number);
        $this->refreshQueueSignals(initial: true);
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
        $query = $this->queueQuery($branchIds)->with('items')->latest();

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

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
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
            'statusLabels' => $this->statusLabels(),
        ])->layout('layouts.admin', ['heading' => 'الكاشير والتحصيل']);
    }

    /**
     * @param  Collection<int, int|string>  $branchIds
     */
    private function queueQuery($branchIds)
    {
        return Order::query()
            ->whereIn('branch_id', $branchIds)
            ->whereIn('status', self::QUEUE_STATUSES);
    }

    /**
     * @return array<string, string>
     */
    private function statusLabels(): array
    {
        return [
            'pending' => 'جديد',
            'confirmed' => 'مؤكد',
            'preparing' => 'قيد التجهيز',
            'ready' => 'جاهز',
            'out_for_delivery' => 'خرج للتوصيل',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
        ];
    }

    private function refreshQueueSignals(bool $initial = false): void
    {
        $user = auth()->user();
        $branchIds = app(BranchAccess::class)->visibleBranchIds($user);

        $pendingCount = Order::query()
            ->whereIn('branch_id', $branchIds)
            ->where('status', 'pending')
            ->count();

        // Includes auto-confirmed cash placements so the beep fires even when nothing is pending.
        $queueCount = Order::query()
            ->whereIn('branch_id', $branchIds)
            ->whereIn('status', self::QUEUE_STATUSES)
            ->count();

        $grew = $pendingCount > $this->lastSeenPendingCount
            || $queueCount > $this->lastSeenQueueCount;

        if (! $initial && ! $this->soundMuted && $grew) {
            $this->dispatch('cashier-new-order-beep');
        }

        $this->pendingCount = $pendingCount;
        $this->lastSeenPendingCount = $pendingCount;
        $this->queueCount = $queueCount;
        $this->lastSeenQueueCount = $queueCount;
    }
}
