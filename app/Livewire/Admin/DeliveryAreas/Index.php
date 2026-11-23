<?php

namespace App\Livewire\Admin\DeliveryAreas;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\DeliveryArea;
use App\Services\BranchAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';
    public string $governorateFilter = '';
    public ?int $branchId = null;
    public ?int $editingId = null;
    public string $governorate = 'محافظة العاصمة';
    public string $name = '';
    public string $deliveryFee = '0.000';
    public string $minimumOrder = '0.000';
    public bool $isActive = true;
    public int $sortOrder = 0;

    public function mount(): void
    {
        $user = auth()->user();

        if (! $user->hasRole('system_admin')) {
            $this->branchId = $user->branch_id;
        } else {
            $this->branchId = Branch::query()->orderBy('id')->value('id');
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('delivery.manage'), 403);

        $branchId = $user->hasRole('system_admin')
            ? (int) $this->branchId
            : (int) $user->branch_id;

        app(BranchAccess::class)->assertCanAccess($user, $branchId);

        $uniqueName = Rule::unique('delivery_areas', 'name')
            ->where(fn ($query) => $query->where('branch_id', $branchId)->where('governorate', $this->governorate));

        if ($this->editingId !== null) {
            $uniqueName->ignore($this->editingId);
        }

        $data = $this->validate([
            'branchId' => ['required', 'integer', 'exists:branches,id'],
            'governorate' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:150', $uniqueName],
            'deliveryFee' => ['required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,3})?$/'],
            'minimumOrder' => ['required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,3})?$/'],
            'isActive' => ['boolean'],
            'sortOrder' => ['integer', 'min:0'],
        ]);

        $before = null;

        DB::transaction(function () use ($data, $branchId, $user, &$before): void {
            if ($this->editingId) {
                $area = DeliveryArea::query()->findOrFail($this->editingId);
                app(BranchAccess::class)->assertCanAccess($user, $area->branch_id);
                $before = $area->only(['governorate', 'name', 'delivery_fee', 'minimum_order', 'is_active', 'sort_order']);
            } else {
                $area = new DeliveryArea(['branch_id' => $branchId]);
            }

            $area->fill([
                'branch_id' => $branchId,
                'governorate' => $data['governorate'],
                'name' => $data['name'],
                'delivery_fee' => $data['deliveryFee'],
                'minimum_order' => $data['minimumOrder'],
                'is_active' => $data['isActive'],
                'sort_order' => $data['sortOrder'],
            ])->save();

            AuditLog::query()->create([
                'branch_id' => $branchId,
                'actor_id' => $user->id,
                'action' => $this->editingId ? 'delivery_area.updated' : 'delivery_area.created',
                'auditable_type' => DeliveryArea::class,
                'auditable_id' => $area->id,
                'before_values' => $before,
                'after_values' => $area->only(['governorate', 'name', 'delivery_fee', 'minimum_order', 'is_active', 'sort_order']),
                'created_at' => now(),
            ]);
        });

        $this->resetForm();
        session()->flash('status', 'تم حفظ منطقة التوصيل.');
    }

    public function edit(int $areaId): void
    {
        $area = DeliveryArea::query()->findOrFail($areaId);
        app(BranchAccess::class)->assertCanAccess(auth()->user(), $area->branch_id);

        $this->editingId = $area->id;
        $this->branchId = $area->branch_id;
        $this->governorate = $area->governorate;
        $this->name = $area->name;
        $this->deliveryFee = (string) $area->delivery_fee;
        $this->minimumOrder = (string) $area->minimum_order;
        $this->isActive = $area->is_active;
        $this->sortOrder = $area->sort_order;
    }

    public function toggleActive(int $areaId): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('delivery.manage'), 403);

        $area = DeliveryArea::query()->findOrFail($areaId);
        app(BranchAccess::class)->assertCanAccess($user, $area->branch_id);
        $before = $area->is_active;
        $area->update(['is_active' => ! $before]);

        AuditLog::query()->create([
            'branch_id' => $area->branch_id,
            'actor_id' => $user->id,
            'action' => 'delivery_area.status_updated',
            'auditable_type' => DeliveryArea::class,
            'auditable_id' => $area->id,
            'before_values' => ['is_active' => $before],
            'after_values' => ['is_active' => ! $before],
            'created_at' => now(),
        ]);
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->governorate = 'محافظة العاصمة';
        $this->name = '';
        $this->deliveryFee = '0.000';
        $this->minimumOrder = '0.000';
        $this->isActive = true;
        $this->sortOrder = 0;
    }

    public function render()
    {
        $user = auth()->user();
        $branchIds = app(BranchAccess::class)->visibleBranchIds($user);
        $query = DeliveryArea::query()->whereIn('branch_id', $branchIds)->orderBy('governorate')->orderBy('sort_order')->orderBy('name');

        if ($this->search !== '') {
            $query->where('name', 'like', '%'.trim($this->search).'%');
        }

        if ($this->governorateFilter !== '') {
            $query->where('governorate', $this->governorateFilter);
        }

        return view('livewire.admin.delivery-areas.index', [
            'areas' => $query->paginate(25),
            'branches' => $user->hasRole('system_admin') ? Branch::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'governorates' => DeliveryArea::query()->whereIn('branch_id', $branchIds)->distinct()->orderBy('governorate')->pluck('governorate'),
            'isSystemAdmin' => $user->hasRole('system_admin'),
        ])->layout('layouts.admin', ['heading' => 'مناطق التوصيل']);
    }
}
