<?php

namespace App\Livewire\Admin\Cashier;

use App\Models\AuditLog;
use App\Models\Order;
use App\Services\BranchAccess;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';
    public string $fulfillmentFilter = '';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFulfillmentFilter(): void { $this->resetPage(); }

    public function collectCash(int $orderId): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('cashier.use'), 403);
        $order = Order::query()->findOrFail($orderId);
        app(BranchAccess::class)->assertCanAccess($user, $order->branch_id);

        $eligible = $order->fulfillment_type === 'pickup'
            ? ['ready', 'completed']
            : ['out_for_delivery', 'completed'];

        abort_if($order->payment_status === 'paid' || ! in_array($order->status, $eligible, true), 422, 'الطلب غير جاهز للتحصيل.');

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

        return view('livewire.admin.cashier.index', [
            'orders' => $query->paginate(25),
        ])->layout('layouts.admin', ['heading' => 'الكاشير والتحصيل']);
    }
}
