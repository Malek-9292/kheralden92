<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DeliveryArea;
use Illuminate\Http\JsonResponse;

class DeliveryAreaController extends Controller
{
    public function index(string $branchCode): JsonResponse
    {
        $branch = Branch::query()
            ->where('code', strtoupper($branchCode))
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'branch' => ['code' => $branch->code, 'name' => $branch->name],
            'delivery_areas' => DeliveryArea::query()
                ->where('branch_id', $branch->id)
                ->where('is_active', true)
                ->orderBy('governorate')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'governorate', 'name', 'delivery_fee', 'minimum_order']),
        ]);
    }
}
