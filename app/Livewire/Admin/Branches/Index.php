<?php

namespace App\Livewire\Admin\Branches;

use App\Models\AuditLog;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public string $timezone = 'Asia/Kuwait';

    public bool $isActive = true;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('branches.manage'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('branches.manage'), 403);

        $data = $this->validate([
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('branches', 'code')->ignore($this->editingId)],
            'name' => ['required', 'string', 'max:150'],
            'timezone' => ['required', 'timezone'],
            'isActive' => ['boolean'],
        ]);

        $branch = DB::transaction(function () use ($data, $user): Branch {
            $before = null;
            if ($this->editingId) {
                $branch = Branch::query()->findOrFail($this->editingId);
                $before = $branch->only(['code', 'name', 'timezone', 'is_active']);
            } else {
                $branch = new Branch;
            }

            $branch->fill([
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
                'timezone' => $data['timezone'],
                'is_active' => $data['isActive'],
            ])->save();

            AuditLog::query()->create([
                'branch_id' => $branch->id,
                'actor_id' => $user->id,
                'action' => $this->editingId ? 'branch.updated' : 'branch.created',
                'auditable_type' => Branch::class,
                'auditable_id' => $branch->id,
                'before_values' => $before,
                'after_values' => $branch->only(['code', 'name', 'timezone', 'is_active']),
                'created_at' => now(),
            ]);

            return $branch;
        });

        $this->resetForm();
        session()->flash('status', 'تم حفظ بيانات الفرع.');
    }

    public function edit(int $id): void
    {
        $branch = Branch::query()->findOrFail($id);
        $this->editingId = $branch->id;
        $this->code = $branch->code;
        $this->name = $branch->name;
        $this->timezone = $branch->timezone;
        $this->isActive = $branch->is_active;
    }

    public function toggleActive(int $id): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('branches.manage'), 403);
        $branch = Branch::query()->findOrFail($id);
        $before = $branch->is_active;
        $branch->update(['is_active' => ! $before]);

        AuditLog::query()->create([
            'branch_id' => $branch->id,
            'actor_id' => $user->id,
            'action' => 'branch.status_updated',
            'auditable_type' => Branch::class,
            'auditable_id' => $branch->id,
            'before_values' => ['is_active' => $before],
            'after_values' => ['is_active' => ! $before],
            'created_at' => now(),
        ]);
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->code = '';
        $this->name = '';
        $this->timezone = 'Asia/Kuwait';
        $this->isActive = true;
    }

    public function render()
    {
        $query = Branch::query()->orderByDesc('is_active')->orderBy('name');
        if ($this->search !== '') {
            $term = trim($this->search);
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('code', 'like', '%'.$term.'%'));
        }

        return view('livewire.admin.branches.index', [
            'branches' => $query->paginate(20),
        ])->layout('layouts.admin', ['heading' => 'إدارة الفروع']);
    }
}
