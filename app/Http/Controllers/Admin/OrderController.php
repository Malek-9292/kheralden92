<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Services\BranchAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request, BranchAccess $branchAccess): JsonResponse
    {
        $query = Order::query()->with('items')->latest();

        if (! $request->user()->hasRole('system_admin')) {
            $query->whereIn('branch_id', $branchAccess->visibleBranchIds($request->user()));
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($search) use ($term): void {
                $search->where('order_number', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_phone', 'like', "%{$term}%");
            });
        }

        if ($request->filled('fulfillment_type')) {
            $query->where('fulfillment_type', $request->input('fulfillment_type'));
        }

        return response()->json(['orders' => $query->paginate(30)]);
    }

    public function show(Request $request, Order $order, BranchAccess $branchAccess): JsonResponse
    {
        $branchAccess->assertCanAccess($request->user(), $order->branch_id);

        return response()->json(['order' => $order->load('items', 'deliveryArea')]);
    }

    public function updateStatus(Request $request, Order $order, BranchAccess $branchAccess): JsonResponse
    {
        $branchAccess->assertCanAccess($request->user(), $order->branch_id);
        $data = $request->validate(['status' => ['required', 'in:confirmed,preparing,ready,out_for_delivery,completed']]);

        $next = $data['status'];
        $allowed = [
            'pending' => ['confirmed'],
            'confirmed' => ['preparing'],
            'preparing' => ['ready'],
            'ready' => $order->fulfillment_type === 'delivery' ? ['out_for_delivery'] : ['completed'],
            'out_for_delivery' => ['completed'],
        ];

        if (! in_array($next, $allowed[$order->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => ['انتقال حالة الطلب غير مسموح من الحالة الحالية.'],
            ]);
        }

        $before = $order->status;

        DB::transaction(function () use ($request, $order, $next, $before): void {
            $order->status = $next;

            if ($next === 'confirmed') {
                $order->confirmed_at = now();
            }

            if ($next === 'completed') {
                $order->completed_at = now();
            }

            $order->save();

            $this->audit($request, $order, 'order.status_updated', [
                'status' => $before,
            ], [
                'status' => $next,
            ]);
        });

        return response()->json([
            'message' => 'تم تحديث حالة الطلب.',
            'order' => $order->fresh()->load('items'),
        ]);
    }

    public function cancel(Request $request, Order $order, BranchAccess $branchAccess): JsonResponse
    {
        $branchAccess->assertCanAccess($request->user(), $order->branch_id);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        if (in_array($order->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'status' => ['لا يمكن إلغاء طلب مكتمل أو ملغي بالفعل.'],
            ]);
        }

        $before = $order->status;

        DB::transaction(function () use ($request, $order, $data, $before): void {
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
            $order->printJobs()->where('status', 'pending')->update(['status' => 'cancelled']);

            $this->audit($request, $order, 'order.cancelled', [
                'status' => $before,
            ], [
                'status' => 'cancelled',
                'reason' => $data['reason'],
            ]);
        });

        return response()->json([
            'message' => 'تم إلغاء الطلب.',
            'order' => $order->fresh()->load('items'),
        ]);
    }

    private function audit(
        Request $request,
        Order $order,
        string $action,
        array $before,
        array $after,
    ): void {
        AuditLog::query()->create([
            'branch_id' => $order->branch_id,
            'actor_id' => $request->user()->id,
            'action' => $action,
            'auditable_type' => Order::class,
            'auditable_id' => $order->id,
            'before_values' => $before,
            'after_values' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'created_at' => now(),
        ]);
    }
}
