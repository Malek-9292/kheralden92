<?php

namespace App\Livewire\Admin\Roles;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Services\BranchAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?int $branchId = null;

    public ?int $editingId = null;

    public string $name = '';

    public string $slug = '';

    public array $permissionSlugs = [];

    public function mount(): void
    {
        $user = auth()->user();
        $this->branchId = $user->hasRole('system_admin')
            ? Branch::query()->orderBy('id')->value('id')
            : $user->branch_id;
    }

    public function save(): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('roles.manage'), 403);
        $branchId = $user->hasRole('system_admin') ? (int) $this->branchId : (int) $user->branch_id;
        app(BranchAccess::class)->assertCanAccess($user, $branchId);

        $role = $this->editingId ? Role::query()->findOrFail($this->editingId) : null;
        if ($role) {
            abort_if($role->is_system || $role->slug === 'system_admin', 403);
            app(BranchAccess::class)->assertCanAccess($user, (int) $role->branch_id);
            abort_unless((int) $role->branch_id === $branchId, 403);
        }

        if (in_array('modules.manage', $this->permissionSlugs, true)) {
            $this->addError('permissionSlugs', 'صلاحية إدارة الوحدات مخصصة لمدير النظام فقط.');
            return;
        }

        $data = $this->validate([
            'branchId' => ['required', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('roles', 'slug')->where(fn ($query) => $query->where('branch_id', $branchId))->ignore($this->editingId),
            ],
            'permissionSlugs' => ['required', 'array', 'min:1'],
            'permissionSlugs.*' => ['string', 'distinct', 'exists:permissions,slug'],
        ]);

        DB::transaction(function () use ($data, $branchId, $user, $role): void {
            $before = $role ? [
                'name' => $role->name,
                'slug' => $role->slug,
                'permission_slugs' => $role->permissions()->pluck('slug')->all(),
            ] : null;

            if ($role === null) {
                $role = new Role([
                    'branch_id' => $branchId,
                    'guard_name' => 'web',
                    'is_system' => false,
                ]);
            }

            $role->fill([
                'branch_id' => $branchId,
                'name' => $data['name'],
                'slug' => $data['slug'],
                'guard_name' => 'web',
                'is_system' => false,
            ])->save();

            $permissionIds = Permission::query()->whereIn('slug', $data['permissionSlugs'])->pluck('id');
            $role->permissions()->sync($permissionIds);

            AuditLog::query()->create([
                'branch_id' => $branchId,
                'actor_id' => $user->id,
                'action' => $this->editingId ? 'role.updated' : 'role.created',
                'auditable_type' => Role::class,
                'auditable_id' => $role->id,
                'before_values' => $before,
                'after_values' => [
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'permission_slugs' => $data['permissionSlugs'],
                ],
                'created_at' => now(),
            ]);
        });

        $this->resetForm();
        session()->flash('status', 'تم حفظ الدور والصلاحيات.');
    }

    public function edit(int $id): void
    {
        $role = Role::query()->with('permissions')->findOrFail($id);
        abort_if($role->is_system || $role->slug === 'system_admin' || $role->branch_id === null, 403);
        app(BranchAccess::class)->assertCanAccess(auth()->user(), $role->branch_id);

        $this->editingId = $role->id;
        $this->branchId = $role->branch_id;
        $this->name = $role->name;
        $this->slug = $role->slug;
        $this->permissionSlugs = $role->permissions->pluck('slug')->all();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->slug = '';
        $this->permissionSlugs = [];
    }

    public function render()
    {
        $user = auth()->user();
        $branchIds = app(BranchAccess::class)->visibleBranchIds($user);
        $roles = Role::query()->where('slug', '!=', 'system_admin')
            ->where(function ($query) use ($branchIds): void {
                $query->whereNull('branch_id')->orWhereIn('branch_id', $branchIds);
            })
            ->with('permissions')->orderBy('name')->get();

        return view('livewire.admin.roles.index', [
            'roles' => $roles,
            'permissionsByModule' => Permission::query()
                ->where('slug', '!=', 'modules.manage')
                ->orderBy('module')->orderBy('name')->get()->groupBy('module'),
            'branches' => $user->hasRole('system_admin') ? Branch::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'isSystemAdmin' => $user->hasRole('system_admin'),
        ])->layout('layouts.admin', ['heading' => 'الأدوار والصلاحيات']);
    }
}
