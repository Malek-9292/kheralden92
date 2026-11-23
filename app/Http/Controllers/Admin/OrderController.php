<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\BranchAccess;
use App\Services\OrderWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function updateStatus(Request $request, Order $order, BranchAccess $branchAccess, OrderWorkflow $workflow): JsonResponse
    {
        $branchAccess->assertCanAccess($request->user(), $order->branch_id);
        $data = $request->validate(['status' => ['required', 'in:confirmed,preparing,ready,out_for_delivery,completed']]);

        $updated = $workflow->transitionTo(
            $order,
            $data['status'],
            $request->user(),
            $request->ip(),
            (string) $request->userAgent(),
        );

        return response()->json([
            'message' => 'تم تحديث حالة الطلب.',
            'order' => $updated,
        ]);
    }

    public function cancel(Request $request, Order $order, BranchAccess $branchAccess, OrderWorkflow $workflow): JsonResponse
    {
        $branchAccess->assertCanAccess($request->user(), $order->branch_id);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $updated = $workflow->cancel(
            $order,
            $request->user(),
            $data['reason'],
            $request->ip(),
            (string) $request->userAgent(),
        );

        return response()->json([
            'message' => 'تم إلغاء الطلب.',
            'order' => $updated,
        ]);
    }
}
