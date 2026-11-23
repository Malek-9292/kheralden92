<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\BranchAccess;
use App\Services\OrderWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashierController extends Controller
{
    public function markPaid(Request $request, Order $order, BranchAccess $branchAccess, OrderWorkflow $workflow): JsonResponse
    {
        $branchAccess->assertCanAccess($request->user(), $order->branch_id);

        $updated = $workflow->collectCash(
            $order,
            $request->user(),
            $request->ip(),
            (string) $request->userAgent(),
        );

        return response()->json([
            'message' => 'تم تسجيل تحصيل المبلغ نقدًا.',
            'order' => $updated,
        ]);
    }
}
