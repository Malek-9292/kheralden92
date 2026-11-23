<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderWorkflow
{
    public function __construct(
        private readonly PrintJobDispatcher $printJobDispatcher,
    ) {}

    public function nextStatus(Order $order): ?string
    {
        return match (true) {
            $order->status === 'pending' => 'confirmed',
            $order->status === 'confirmed' => 'preparing',
            $order->status === 'preparing' => 'ready',
            $order->status === 'ready' && $order->fulfillment_type === 'delivery' => 'out_for_delivery',
            $order->status === 'ready' => 'completed',
            $order->status === 'out_for_delivery' => 'completed',
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    public function allowedTransitions(Order $order): array
    {
        return match ($order->status) {
            'pending' => ['confirmed'],
            'confirmed' => ['preparing'],
            'preparing' => ['ready'],
            'ready' => $order->fulfillment_type === 'delivery' ? ['out_for_delivery'] : ['completed'],
            'out_for_delivery' => ['completed'],
            default => [],
        };
    }

    public function advance(Order $order, User $actor, ?string $ip = null, ?string $userAgent = null): Order
    {
        $next = $this->nextStatus($order);

        if ($next === null) {
            throw ValidationException::withMessages([
                'status' => ['لا توجد خطوة تالية متاحة لهذا الطلب.'],
            ]);
        }

        return $this->transitionTo($order, $next, $actor, $ip, $userAgent);
    }

    public function transitionTo(
        Order $order,
        string $next,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Order {
        if (! in_array($next, $this->allowedTransitions($order), true)) {
            throw ValidationException::withMessages([
                'status' => ['انتقال حالة الطلب غير مسموح من الحالة الحالية.'],
            ]);
        }

        $before = $order->status;

        return DB::transaction(function () use ($order, $next, $before, $actor, $ip, $userAgent): Order {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($next, $this->allowedTransitions($locked), true)) {
                throw ValidationException::withMessages([
                    'status' => ['انتقال حالة الطلب غير مسموح من الحالة الحالية.'],
                ]);
            }

            $locked->status = $next;

            if ($next === 'confirmed') {
                $locked->confirmed_at = now();
            }

            if ($next === 'completed') {
                $locked->completed_at = now();
            }

            $locked->save();

            $this->audit($locked, $actor, 'order.status_updated', [
                'status' => $before,
            ], [
                'status' => $next,
            ], $ip, $userAgent);

            // DEC-095: kitchen ticket prints when the order is accepted (confirmed), not on create.
            if ($next === 'confirmed') {
                $this->printJobDispatcher->enqueueKitchenTicket($locked->fresh()->load('items'));
            }

            return $locked->fresh()->load('items');
        });
    }

    public function cancel(
        Order $order,
        User $actor,
        string $reason,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Order {
        if (in_array($order->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'status' => ['لا يمكن إلغاء طلب مكتمل أو ملغي بالفعل.'],
            ]);
        }

        $before = $order->status;

        return DB::transaction(function () use ($order, $before, $actor, $reason, $ip, $userAgent): Order {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, ['completed', 'cancelled'], true)) {
                throw ValidationException::withMessages([
                    'status' => ['لا يمكن إلغاء طلب مكتمل أو ملغي بالفعل.'],
                ]);
            }

            $locked->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
            $locked->printJobs()->where('status', 'pending')->update(['status' => 'cancelled']);

            $this->audit($locked, $actor, 'order.cancelled', [
                'status' => $before,
            ], [
                'status' => 'cancelled',
                'reason' => $reason,
            ], $ip, $userAgent, $reason);

            return $locked->fresh()->load('items');
        });
    }

    public function collectCash(
        Order $order,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Order {
        return DB::transaction(function () use ($order, $actor, $ip, $userAgent): Order {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->payment_status === 'paid') {
                throw ValidationException::withMessages([
                    'payment_status' => ['تم تسجيل سداد هذا الطلب بالفعل.'],
                ]);
            }

            $eligible = $locked->fulfillment_type === 'pickup'
                ? ['ready', 'completed']
                : ['out_for_delivery', 'completed'];

            if (! in_array($locked->status, $eligible, true)) {
                throw ValidationException::withMessages([
                    'status' => ['لا يمكن تحصيل قيمة الطلب قبل وصوله لمرحلة التسليم.'],
                ]);
            }

            $locked->update([
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            $this->printJobDispatcher->enqueueCustomerReceipt($locked->fresh()->load('items'));

            $this->audit($locked, $actor, 'order.payment_collected', [
                'payment_status' => 'unpaid',
            ], [
                'payment_status' => 'paid',
                'payment_method' => 'cash',
            ], $ip, $userAgent);

            return $locked->fresh()->load('items');
        });
    }

    private function audit(
        Order $order,
        User $actor,
        string $action,
        array $before,
        array $after,
        ?string $ip = null,
        ?string $userAgent = null,
        ?string $reason = null,
    ): void {
        AuditLog::query()->create([
            'branch_id' => $order->branch_id,
            'actor_id' => $actor->id,
            'action' => $action,
            'auditable_type' => Order::class,
            'auditable_id' => $order->id,
            'before_values' => $before,
            'after_values' => $after,
            'reason' => $reason,
            'ip_address' => $ip,
            'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
            'created_at' => now(),
        ]);
    }
}
