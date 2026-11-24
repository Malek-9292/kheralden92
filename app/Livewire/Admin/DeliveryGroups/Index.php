<?php

namespace App\Livewire\Admin\DeliveryGroups;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\DeliveryArea;
use App\Models\DeliveryAreaGroup;
use App\Services\BranchAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Index extends Component
{
    public ?int $branchId = null;

    public ?int $editingGroupId = null;

    public string $groupName = '';

    public string $deliveryFee = '0.000';

    public string $minimumOrder = '0.000';

    public bool $groupActive = true;

    public ?int $selectedAreaId = null;

    public ?int $selectedGroupId = null;

    public bool $customPricing = false;

    public string $customDeliveryFee = '0.000';

    public string $customMinimumOrder = '0.000';

    public function mount(): void
    {
        $user = auth()->user();
        $this->branchId = $user->hasRole('system_admin')
            ? Branch::query()->orderBy('id')->value('id')
            : $user->branch_id;
    }

    private function authorizedBranch(): int
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('delivery.manage'), 403);
        $branchId = $user->hasRole('system_admin') ? (int) $this->branchId : (int) $user->branch_id;
        app(BranchAccess::class)->assertCanAccess($user, $branchId);


        return $branchId;
    }

    public function saveGroup(): void
    {
        $branchId = $this->authorizedBranch();
        $unique = Rule::unique('delivery_area_groups', 'name')->where('branch_id', $branchId);
        if ($this->editingGroupId) {
            $unique->ignore($this->editingGroupId);
        }

        $data = $this->validate([
            'groupName' => ['required', 'string', 'max:120', $unique],
            'deliveryFee' => ['required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,3})?$/'],
            'minimumOrder' => ['required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,3})?$/'],
            'groupActive' => ['boolean'],
        ]);

        DB::transaction(function () use ($branchId, $data): void {
            $group = $this->editingGroupId
                ? DeliveryAreaGroup::query()->where('branch_id', $branchId)->findOrFail($this->editingGroupId)
                : new DeliveryAreaGroup(['branch_id' => $branchId]);
            $before = $group->exists ? $group->only(['name', 'delivery_fee', 'minimum_order', 'is_active']) : null;
            $group->fill([
                'branch_id' => $branchId,
                'name' => $data['groupName'],
                'delivery_fee' => $data['deliveryFee'],
                'minimum_order' => $data['minimumOrder'],
                'is_active' => $data['groupActive'],
            ])->save();

            AuditLog::query()->create([
                'branch_id' => $branchId,
                'actor_id' => auth()->id(),
                'action' => $before ? 'delivery_group.updated' : 'delivery_group.created',
                'auditable_type' => DeliveryAreaGroup::class,
                'auditable_id' => $group->id,
                'before_values' => $before,
                'after_values' => $group->only(['name', 'delivery_fee', 'minimum_order', 'is_active']),
                'created_at' => now(),
            ]);
        });

        $this->resetGroupForm();
        session()->flash('status', 'تم حفظ مجموعة التوصيل.');
    }

    public function editGroup(int $id): void
    {
        $branchId = $this->authorizedBranch();
        $group = DeliveryAreaGroup::query()->where('branch_id', $branchId)->findOrFail($id);
        $this->editingGroupId = $group->id;
        $this->groupName = $group->name;
        $this->deliveryFee = (string) $group->delivery_fee;
        $this->minimumOrder = (string) $group->minimum_order;
        $this->groupActive = $group->is_active;
    }

    public function resetGroupForm(): void
    {
        $this->reset(['editingGroupId', 'groupName']);
        $this->deliveryFee = '0.000';
        $this->minimumOrder = '0.000';
        $this->groupActive = true;
    }

    public function selectArea(int $id): void
    {
        $branchId = $this->authorizedBranch();
        $area = DeliveryArea::query()->where('branch_id', $branchId)->findOrFail($id);
        $this->selectedAreaId = $area->id;
        $this->selectedGroupId = $area->delivery_area_group_id;
        $this->customPricing = $area->delivery_fee_override !== null || $area->minimum_order_override !== null;
        $this->customDeliveryFee = (string) ($area->delivery_fee_override ?? $area->delivery_fee);
        $this->customMinimumOrder = (string) ($area->minimum_order_override ?? $area->minimum_order);
    }

    public function saveAreaAssignment(): void
    {
        $branchId = $this->authorizedBranch();
        $data = $this->validate([
            'selectedAreaId' => ['required', 'integer', Rule::exists('delivery_areas', 'id')->where('branch_id', $branchId)],
            'selectedGroupId' => ['required', 'integer', Rule::exists('delivery_area_groups', 'id')->where('branch_id', $branchId)],
            'customPricing' => ['boolean'],
            'customDeliveryFee' => ['required_if:customPricing,true', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,3})?$/'],
            'customMinimumOrder' => ['required_if:customPricing,true', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,3})?$/'],
        ]);

        DB::transaction(function () use ($branchId, $data): void {
            $area = DeliveryArea::query()->where('branch_id', $branchId)->lockForUpdate()->findOrFail($data['selectedAreaId']);
            $before = $area->only(['delivery_area_group_id', 'delivery_fee_override', 'minimum_order_override']);
            $area->update([
                'delivery_area_group_id' => $data['selectedGroupId'],
                'delivery_fee_override' => $data['customPricing'] ? $data['customDeliveryFee'] : null,
                'minimum_order_override' => $data['customPricing'] ? $data['customMinimumOrder'] : null,
            ]);

            AuditLog::query()->create([
                'branch_id' => $branchId,
                'actor_id' => auth()->id(),
                'action' => 'delivery_area.group_pricing_updated',
                'auditable_type' => DeliveryArea::class,
                'auditable_id' => $area->id,
                'before_values' => $before,
                'after_values' => $area->only(['delivery_area_group_id', 'delivery_fee_override', 'minimum_order_override']),
                'created_at' => now(),
            ]);
        });

        session()->flash('status', 'تم تحديث مجموعة المنطقة وأسعارها.');
        $this->selectArea((int) $this->selectedAreaId);
    }

    public function render()
    {
        $branchId = $this->authorizedBranch();

        return view('livewire.admin.delivery-groups.index', [
            'branches' => auth()->user()->hasRole('system_admin') ? Branch::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'groups' => DeliveryAreaGroup::query()->where('branch_id', $branchId)->withCount('areas')->orderBy('sort_order')->orderBy('name')->get(),
            'areas' => DeliveryArea::query()->where('branch_id', $branchId)->with('group')->orderBy('governorate')->orderBy('name')->get(),
        ])->layout('layouts.admin', ['heading' => 'مجموعات مناطق التوصيل']);
    }
}
