<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use App\Services\BranchAccess;
use App\Services\OrderWorkflow;
use App\Services\PrintJobDispatcher;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public string $statusFilter = '';

    public string $fulfillmentFilter = '';

    public ?int $cancelOrderId = null;

    public string $cancelReason = '';

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

    public function advance(int $orderId, OrderWorkflow $workflow): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('orders.accept'), 403);

        $order = Order::query()->findOrFail($orderId);
        app(BranchAccess::class)->assertCanAccess($user, $order->branch_id);

        $workflow->advance($order, $user);
        session()->flash('status', 'تم تحديث حالة الطلب.');
    }

    public function openCancelModal(int $orderId): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('orders.cancel'), 403);

        $order = Order::query()->findOrFail($orderId);
        app(BranchAccess::class)->assertCanAccess($user, $order->branch_id);

        $this->cancelOrderId = $order->id;
        $this->cancelReason = '';
        $this->resetErrorBag('cancelReason');
    }

    public function closeCancelModal(): void
    {
        $this->cancelOrderId = null;
        $this->cancelReason = '';
        $this->resetErrorBag('cancelReason');
    }

    public function confirmCancel(OrderWorkflow $workflow): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('orders.cancel'), 403);

        $data = $this->validate([
            'cancelOrderId' => ['required', 'integer'],
            'cancelReason' => ['required', 'string', 'min:3', 'max:500'],
        ], [], [
            'cancelReason' => 'سبب الإلغاء',
        ]);

        $order = Order::query()->findOrFail((int) $data['cancelOrderId']);
        app(BranchAccess::class)->assertCanAccess($user, $order->branch_id);

        $workflow->cancel($order, $user, trim($data['cancelReason']));
        $this->closeCancelModal();
        session()->flash('status', 'تم إلغاء الطلب.');
    }

    public function collectCash(int $orderId, OrderWorkflow $workflow): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('cashier.use'), 403);

        $order = Order::query()->findOrFail($orderId);
        app(BranchAccess::class)->assertCanAccess($user, $order->branch_id);

        $workflow->collectCash($order, $user);
        session()->flash('status', 'تم تسجيل التحصيل النقدي.');
    }

    public function reprintKitchen(int $orderId, PrintJobDispatcher $dispatcher): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('orders.accept') || $user->hasPermissionTo('printing.manage'), 403);

        $order = Order::query()->findOrFail($orderId);
        app(BranchAccess::class)->assertCanAccess($user, $order->branch_id);

        $dispatcher->reprintKitchenTicket($order->load('items'));
        session()->flash('status', 'تمت إعادة تذكرة المطبخ إلى طابور الطباعة.');
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
            'canReprint' => $user->hasPermissionTo('orders.accept') || $user->hasPermissionTo('printing.manage'),
        ])->layout('layouts.admin', ['heading' => 'إدارة الطلبات']);
    }
}
