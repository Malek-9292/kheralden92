<?php

namespace App\Livewire\Admin\Employees;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Services\BranchAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';
    public string $statusFilter = '';
    public ?int $branchId = null;
    public ?int $editingId = null;
    public string $name = '';
    public string $phone = '';
    public string $password = '';
    public string $status = 'active';
    public string $validFrom = '';
    public string $validUntil = '';
    public array $roleIds = [];

    public function mount(): void
    {
        $user = auth()->user();
        $this->branchId = $user->hasRole('system_admin')
            ? Branch::query()->orderBy('id')->value('id')
            : $user->branch_id;
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }

    public function save(): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('employees.manage'), 403);
        $branchId = $user->hasRole('system_admin') ? (int) $this->branchId : (int) $user->branch_id;
        app(BranchAccess::class)->assertCanAccess($user, $branchId);

        $employee = $this->editingId ? User::query()->findOrFail($this->editingId) : null;
        if ($employee) {
            abort_if($employee->hasRole('system_admin'), 403);
            app(BranchAccess::class)->assertCanAccess($user, $employee->branch_id);
            abort_unless($employee->branch_id === $branchId || $user->hasRole('system_admin'), 403);
        }

        $data = $this->validate([
            'branchId' => ['required', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($this->editingId)],
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:12', 'max:200'],
            'status' => ['required', Rule::in(['active', 'suspended', 'inactive'])],
            'validFrom' => ['nullable', 'date'],
            'validUntil' => ['nullable', 'date', 'after_or_equal:validFrom'],
            'roleIds' => ['required', 'array', 'min:1'],
            'roleIds.*' => ['integer', 'distinct', 'exists:roles,id'],
        ]);

        $allowedRoles = Role::query()
            ->whereIn('id', $data['roleIds'])
            ->where(function ($query) use ($branchId): void {
                $query->whereNull('branch_id')->orWhere('branch_id', $branchId);
            })
            ->where('slug', '!=', 'system_admin')
            ->get();

        if ($allowedRoles->count() !== count(array_unique($data['roleIds']))) {
            $this->addError('roleIds', 'الأدوار المختارة غير متاحة لهذا الفرع.');
            return;
        }

        DB::transaction(function () use ($data, $branchId, $user, $employee): void {
            $before = $employee?->only(['name', 'phone', 'branch_id', 'status', 'valid_from', 'valid_until']);
            $beforeRoles = $employee?->roles()->pluck('roles.id')->all() ?? [];

            if (! $employee) {
                $employee = new User();
            }

            $employee->fill([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'branch_id' => $branchId,
                'status' => $data['status'],
                'valid_from' => $data['validFrom'] ?: null,
                'valid_until' => $data['validUntil'] ?: null,
            ]);

            if (filled($data['password'])) {
                $employee->password = Hash::make($data['password']);
            }

            $employee->save();
            $employee->roles()->sync($data['roleIds']);

            AuditLog::query()->create([
                'branch_id' => $branchId,
                'actor_id' => $user->id,
                'action' => $this->editingId ? 'employee.updated' : 'employee.created',
                'auditable_type' => User::class,
                'auditable_id' => $employee->id,
                'before_values' => $before ? [...$before, 'role_ids' => $beforeRoles] : null,
                'after_values' => [
                    'name' => $employee->name,
                    'phone' => $employee->phone,
                    'branch_id' => $employee->branch_id,
                    'status' => $employee->status,
                    'valid_from' => $employee->valid_from,
                    'valid_until' => $employee->valid_until,
                    'role_ids' => $data['roleIds'],
                ],
                'created_at' => now(),
            ]);

            $branchChanged = $before && (int) $before['branch_id'] !== $branchId;
            $validityChanged = $before && (
                (string) ($before['valid_from'] ?? '') !== (string) ($employee->valid_from ?? '')
                || (string) ($before['valid_until'] ?? '') !== (string) ($employee->valid_until ?? '')
            );
            $statusChangedToInactive = $employee->status !== 'active';
            $passwordChanged = filled($data['password']);
            if ($employee->exists && ($branchChanged || $validityChanged || $statusChangedToInactive || $passwordChanged)) {
                $employee->employeeSessions()->whereNull('logged_out_at')->update([
                    'logged_out_at' => now(),
                    'ended_reason' => 'account_changed',
                ]);
            }
        });

        $this->resetForm();
        session()->flash('status', 'تم حفظ بيانات الموظف.');
    }

    public function edit(int $id): void
    {
        $employee = User::query()->with('roles')->findOrFail($id);
        abort_if($employee->hasRole('system_admin'), 403);
        app(BranchAccess::class)->assertCanAccess(auth()->user(), $employee->branch_id);

        $this->editingId = $employee->id;
        $this->branchId = $employee->branch_id;
        $this->name = $employee->name;
        $this->phone = $employee->phone;
        $this->password = '';
        $this->status = $employee->status;
        $this->validFrom = $employee->valid_from?->format('Y-m-d\TH:i') ?? '';
        $this->validUntil = $employee->valid_until?->format('Y-m-d\TH:i') ?? '';
        $this->roleIds = $employee->roles->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->phone = '';
        $this->password = '';
        $this->status = 'active';
        $this->validFrom = '';
        $this->validUntil = '';
        $this->roleIds = [];
    }

    public function render()
    {
        $user = auth()->user();
        $branchIds = app(BranchAccess::class)->visibleBranchIds($user);
        $query = User::query()->whereIn('branch_id', $branchIds)->whereDoesntHave('roles', fn ($q) => $q->where('slug', 'system_admin'))->with('roles')->orderBy('name');

        if ($this->search !== '') {
            $term = trim($this->search);
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('phone', 'like', '%'.$term.'%'));
        }
        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        $selectedBranchId = $user->hasRole('system_admin') ? (int) $this->branchId : (int) $user->branch_id;

        return view('livewire.admin.employees.index', [
            'employees' => $query->paginate(20),
            'branches' => $user->hasRole('system_admin') ? Branch::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'roles' => Role::query()->where(function ($q) use ($selectedBranchId): void {
                $q->whereNull('branch_id')->orWhere('branch_id', $selectedBranchId);
            })->where('slug', '!=', 'system_admin')->orderBy('name')->get(),
            'isSystemAdmin' => $user->hasRole('system_admin'),
        ])->layout('layouts.admin', ['heading' => 'إدارة الموظفين']);
    }
}
