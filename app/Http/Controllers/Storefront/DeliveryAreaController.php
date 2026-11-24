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
            ->where(function ($query) {\n                $query->whereNull('delivery_area_group_id')\n                    ->orWhereHas('group', fn ($group) => $group->where('is_active', true));\n            })
            ->with(['group:id,name,delivery_fee,minimum_order,is_active'])
            ->orderBy('governorate')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'governorate', 'name', 'delivery_fee', 'minimum_order', 'delivery_area_group_id', 'delivery_fee_override', 'minimum_order_override'])
            ->map(function (DeliveryArea $area): array {
                return [
                    'id' => $area->id,
                    'governorate' => $area->governorate,
                    'name' => $area->name,
                    'group_name' => $area->group?->name,
                    'delivery_fee' => $area->effectiveDeliveryFee(),
                    'minimum_order' => $area->effectiveMinimumOrder(),
                ];
            });

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
