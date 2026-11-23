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

        return view('livewire.admin.cashier.index', [
            'orders' => $query->paginate(25),
        ])->layout('layouts.admin', ['heading' => 'الكاشير والتحصيل']);
    }
}
