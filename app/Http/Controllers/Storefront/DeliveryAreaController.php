<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DeliveryArea;
use App\Support\Delivery\KuwaitAreas;
use Illuminate\Http\JsonResponse;

class DeliveryAreaController extends Controller
{
    public function index(string $branchCode): JsonResponse
    {
        $branch = Branch::query()
            ->where('code', strtoupper($branchCode))
            ->where('is_active', true)
            ->firstOrFail();

        $areas = DeliveryArea::query()
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->orderBy('governorate')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'governorate', 'name', 'delivery_fee', 'minimum_order']);

        $grouped = $areas
            ->groupBy('governorate')
            ->map(fn ($items, $governorate) => [
                'governorate' => $governorate,
                'areas' => $items->values(),
            ])
            ->values();

        return response()->json([
            'branch' => ['code' => $branch->code, 'name' => $branch->name],
            'governorates' => KuwaitAreas::governorates(),
            'delivery_areas' => $areas,
            'delivery_areas_by_governorate' => $grouped,
        ]);
    }
}
