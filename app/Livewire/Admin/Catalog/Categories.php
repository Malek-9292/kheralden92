<?php

namespace App\Livewire\Admin\Catalog;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Category;
use App\Services\BranchAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Categories extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $branchId = null;
    public ?int $editingId = null;
    public string $name = '';
    public ?int $parentId = null;
    public int $sortOrder = 0;
    public bool $isActive = true;

    public function mount(): void
    {
        $user = auth()->user();
        $this->branchId = $user->hasRole('system_admin')
            ? Branch::query()->orderBy('id')->value('id')
            : $user->branch_id;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('catalog.manage'), 403);
        $branchId = $user->hasRole('system_admin') ? (int) $this->branchId : (int) $user->branch_id;
        app(BranchAccess::class)->assertCanAccess($user, $branchId);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'parentId' => ['nullable', 'integer', 'exists:categories,id'],
            'sortOrder' => ['integer', 'min:0'],
            'isActive' => ['boolean'],
        ]);

        if ($data['parentId']) {
            abort_unless(
                Category::query()->whereKey($data['parentId'])->where('branch_id', $branchId)
                    ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))->exists(),
                422,
                'القسم الأب غير تابع للفرع المحدد.',
            );
        }

        DB::transaction(function () use ($data, $branchId, $user): void {
            $before = null;
            if ($this->editingId) {
                $category = Category::query()->findOrFail($this->editingId);
                app(BranchAccess::class)->assertCanAccess($user, $category->branch_id);
                $before = $category->only(['name', 'parent_id', 'is_active', 'sort_order']);
            } else {
                $category = new Category(['branch_id' => $branchId]);
            }

            $category->fill([
                'branch_id' => $branchId,
                'parent_id' => $data['parentId'],
                'name' => $data['name'],
                'slug' => $category->exists ? $category->slug : $this->uniqueSlug($data['name'], $branchId),
                'sort_order' => $data['sortOrder'],
                'is_active' => $data['isActive'],
            ])->save();

            AuditLog::query()->create([
                'branch_id' => $branchId,
                'actor_id' => $user->id,
                'action' => $this->editingId ? 'category.updated' : 'category.created',
                'auditable_type' => Category::class,
                'auditable_id' => $category->id,
                'before_values' => $before,
                'after_values' => $category->only(['name', 'parent_id', 'is_active', 'sort_order']),
                'created_at' => now(),
            ]);
        });

        $this->resetForm();
        session()->flash('status', 'تم حفظ القسم بنجاح.');
    }

    public function edit(int $id): void
    {
        $category = Category::query()->findOrFail($id);
        app(BranchAccess::class)->assertCanAccess(auth()->user(), $category->branch_id);
        $this->editingId = $category->id;
        $this->branchId = $category->branch_id;
        $this->name = $category->name;
        $this->parentId = $category->parent_id;
        $this->sortOrder = $category->sort_order;
        $this->isActive = $category->is_active;
    }

    public function toggleActive(int $id): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('catalog.manage'), 403);
        $category = Category::query()->findOrFail($id);
        app(BranchAccess::class)->assertCanAccess($user, $category->branch_id);
        $before = $category->is_active;
        $category->update(['is_active' => ! $before]);
        AuditLog::query()->create([
            'branch_id' => $category->branch_id,
            'actor_id' => $user->id,
            'action' => 'category.status_updated',
            'auditable_type' => Category::class,
            'auditable_id' => $category->id,
            'before_values' => ['is_active' => $before],
            'after_values' => ['is_active' => ! $before],
            'created_at' => now(),
        ]);
    }

    public function delete(int $id): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('catalog.manage'), 403);
        $category = Category::query()->findOrFail($id);
        app(BranchAccess::class)->assertCanAccess($user, $category->branch_id);
        abort_if($category->children()->exists() || $category->products()->exists(), 409, 'انقل الأصناف والأقسام الفرعية قبل حذف القسم.');
        $category->delete();
        AuditLog::query()->create([
            'branch_id' => $category->branch_id,
            'actor_id' => $user->id,
            'action' => 'category.deleted',
            'auditable_type' => Category::class,
            'auditable_id' => $category->id,
            'before_values' => ['name' => $category->name, 'slug' => $category->slug],
            'created_at' => now(),
        ]);
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->parentId = null;
        $this->sortOrder = 0;
        $this->isActive = true;
    }

    private function uniqueSlug(string $name, int $branchId): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $suffix = 2;
        while (Category::withTrashed()->where('branch_id', $branchId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }
        return $slug;
    }

    public function render()
    {
        $user = auth()->user();
        $branchIds = app(BranchAccess::class)->visibleBranchIds($user);
        $query = Category::query()->whereIn('branch_id', $branchIds)->withCount('products')->orderBy('sort_order')->orderBy('name');
        if ($this->search !== '') {
            $query->where('name', 'like', '%'.trim($this->search).'%');
        }

        return view('livewire.admin.catalog.categories', [
            'categories' => $query->paginate(25),
            'parents' => Category::query()->whereIn('branch_id', $branchIds)
                ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
                ->orderBy('sort_order')->get(['id', 'name']),
            'branches' => $user->hasRole('system_admin') ? Branch::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'isSystemAdmin' => $user->hasRole('system_admin'),
        ])->layout('layouts.admin', ['heading' => 'إدارة الأقسام']);
    }
}
