<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Services\BranchAccess;
use App\Services\PrintJobDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashierController extends Controller
{
    public function markPaid(Request $request, PrintJobDispatcher $printJobDispatcher, Order $order, BranchAccess $branchAccess): JsonResponse
    {
        $branchAccess->assertCanAccess($request->user(), $order->branch_id);

        if ($order->payment_status === 'paid') {
            throw ValidationException::withMessages([
                'payment_status' => ['تم تسجيل سداد هذا الطلب بالفعل.'],
            ]);
        }

        $eligibleStatuses = $order->fulfillment_type === 'pickup'
            ? ['ready', 'completed']
            : ['out_for_delivery', 'completed'];

        if (! in_array($order->status, $eligibleStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => ['لا يمكن تحصيل قيمة الطلب قبل وصوله لمرحلة التسليم.'],
            ]);
        }

        DB::transaction(function () use ($request, $order, $printJobDispatcher): void {
            $order->update([
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            $printJobDispatcher->enqueueCustomerReceipt($order->fresh()->load('items'));

            AuditLog::query()->create([
                'branch_id' => $order->branch_id,
                'actor_id' => $request->user()->id,
                'action' => 'order.payment_collected',
                'auditable_type' => Order::class,
                'auditable_id' => $order->id,
                'before_values' => ['payment_status' => 'unpaid'],
                'after_values' => ['payment_status' => 'paid', 'payment_method' => 'cash'],
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'created_at' => now(),
            ]);
        });

        return response()->json([
            'message' => 'تم تسجيل تحصيل المبلغ نقدًا.',
            'order' => $order->fresh()->load('items'),
        ]);
    }
}
