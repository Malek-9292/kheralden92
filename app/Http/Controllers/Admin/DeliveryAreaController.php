<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DeliveryArea;
use App\Services\BranchAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DeliveryAreaController extends Controller
{
    public function index(Request $request, BranchAccess $branchAccess): JsonResponse
    {
        $query = DeliveryArea::query()->orderBy('governorate')->orderBy('sort_order')->orderBy('name');

        if (! $request->user()->hasRole('system_admin')) {
            $query->whereIn('branch_id', $branchAccess->visibleBranchIds($request->user()));
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        if ($request->filled('governorate')) {
            $query->where('governorate', $request->string('governorate')->trim()->toString());
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where('name', 'like', "%{$term}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json(['delivery_areas' => $query->paginate(50)]);
    }

    public function store(Request $request, BranchAccess $branchAccess): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'governorate' => ['required', 'string', 'max:100'],
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('delivery_areas', 'name')->where(fn ($query) => $query->where('branch_id', $branchId)->where('governorate', $request->input('governorate'))),
            ],
            'delivery_fee' => ['required', 'numeric', 'min:0', 'regex:/^\\d+(\\.\\d{1,3})?$/'],
            'minimum_order' => ['required', 'numeric', 'min:0', 'regex:/^\\d+(\\.\\d{1,3})?$/'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $branchId = (int) $data['branch_id'];
        $branchAccess->assertCanAccess($request->user(), $branchId);

        $area = DB::transaction(function () use ($data, $branchId, $request): DeliveryArea {
            $area = DeliveryArea::query()->create([
                ...$data,
                'branch_id' => $branchId,
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $this->audit($request, $area, 'delivery_area.created', null, $area->only([
                'governorate', 'name', 'delivery_fee', 'minimum_order', 'is_active', 'sort_order',
            ]));

            return $area;
        });

        return response()->json([
            'message' => 'تم إضافة منطقة التوصيل.',
            'delivery_area' => $area,
        ], 201);
    }

    public function update(Request $request, DeliveryArea $deliveryArea, BranchAccess $branchAccess): JsonResponse
    {
        $branchAccess->assertCanAccess($request->user(), $deliveryArea->branch_id);

        $data = $request->validate([
            'governorate' => ['sometimes', 'required', 'string', 'max:100'],
            'name' => [
                'sometimes', 'required', 'string', 'max:150',
                Rule::unique('delivery_areas', 'name')
                    ->where(fn ($query) => $query->where('branch_id', $deliveryArea->branch_id)->where('governorate', $request->input('governorate', $deliveryArea->governorate)))
                    ->ignore($deliveryArea->id),
            ],
            'delivery_fee' => ['sometimes', 'required', 'numeric', 'min:0', 'regex:/^\\d+(\\.\\d{1,3})?$/'],
            'minimum_order' => ['sometimes', 'required', 'numeric', 'min:0', 'regex:/^\\d+(\\.\\d{1,3})?$/'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $before = $deliveryArea->only([
            'governorate', 'name', 'delivery_fee', 'minimum_order', 'is_active', 'sort_order',
        ]);

        DB::transaction(function () use ($deliveryArea, $data, $before, $request): void {
            $deliveryArea->fill($data)->save();

            $this->audit(
                $request,
                $deliveryArea,
                'delivery_area.updated',
                $before,
                $deliveryArea->only([
                    'governorate', 'name', 'delivery_fee', 'minimum_order', 'is_active', 'sort_order',
                ]),
            );
        });

        return response()->json([
            'message' => 'تم تحديث منطقة التوصيل.',
            'delivery_area' => $deliveryArea->fresh(),
        ]);
    }

    private function audit(
        Request $request,
        DeliveryArea $area,
        string $action,
        ?array $before,
        array $after,
    ): void {
        AuditLog::query()->create([
            'branch_id' => $area->branch_id,
            'actor_id' => $request->user()->id,
            'action' => $action,
            'auditable_type' => DeliveryArea::class,
            'auditable_id' => $area->id,
            'before_values' => $before,
            'after_values' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'created_at' => now(),
        ]);
    }
}
