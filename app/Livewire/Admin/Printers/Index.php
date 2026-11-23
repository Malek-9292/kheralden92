<?php

namespace App\Livewire\Admin\Printers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Printer;
use App\Services\BranchAccess;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?int $branchId = null;
    public string $code = '';
    public string $name = '';
    public string $purpose = 'kitchen';
    public string $connectionType = 'usb';
    public ?string $newDeviceToken = null;

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
        abort_unless($user->hasPermissionTo('printing.manage'), 403);
        $branchId = $user->hasRole('system_admin') ? (int) $this->branchId : (int) $user->branch_id;
        app(BranchAccess::class)->assertCanAccess($user, $branchId);

        $data = $this->validate([
            'branchId' => ['required', 'integer', 'exists:branches,id'],
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/', 'unique:printers,code,NULL,id,branch_id,'.$branchId],
            'name' => ['required', 'string', 'max:150'],
            'purpose' => ['required', 'in:kitchen,cashier'],
            'connectionType' => ['required', 'in:usb,network'],
        ]);

        $token = Str::random(64);
        $printer = Printer::query()->create([
            'branch_id' => $branchId,
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'purpose' => $data['purpose'],
            'connection_type' => $data['connectionType'],
            'device_token_hash' => hash('sha256', $token),
            'is_active' => true,
        ]);

        AuditLog::query()->create([
            'branch_id' => $branchId,
            'actor_id' => $user->id,
            'action' => 'printer.created',
            'auditable_type' => Printer::class,
            'auditable_id' => $printer->id,
            'after_values' => $printer->only(['code', 'name', 'purpose', 'connection_type', 'is_active']),
            'created_at' => now(),
        ]);

        $this->newDeviceToken = $token;
        $this->code = '';
        $this->name = '';
        session()->flash('status', 'تم تسجيل الطابعة. انسخ رمز الجهاز واحفظه في Print Agent الآن.');
    }

    public function rotateToken(int $printerId): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('printing.manage'), 403);
        $printer = Printer::query()->findOrFail($printerId);
        app(BranchAccess::class)->assertCanAccess($user, $printer->branch_id);
        $token = Str::random(64);
        $printer->update(['device_token_hash' => hash('sha256', $token)]);
        $this->newDeviceToken = $token;

        AuditLog::query()->create([
            'branch_id' => $printer->branch_id,
            'actor_id' => $user->id,
            'action' => 'printer.token_rotated',
            'auditable_type' => Printer::class,
            'auditable_id' => $printer->id,
            'created_at' => now(),
        ]);
    }

    public function toggleActive(int $printerId): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('printing.manage'), 403);
        $printer = Printer::query()->findOrFail($printerId);
        app(BranchAccess::class)->assertCanAccess($user, $printer->branch_id);
        $before = $printer->is_active;
        $printer->update(['is_active' => ! $before]);

        if ($before) {
            $printer->jobs()->whereIn('status', ['pending', 'processing'])->update(['printer_id' => null, 'status' => 'pending', 'claimed_at' => null]);
        }

        AuditLog::query()->create([
            'branch_id' => $printer->branch_id,
            'actor_id' => $user->id,
            'action' => 'printer.status_updated',
            'auditable_type' => Printer::class,
            'auditable_id' => $printer->id,
            'before_values' => ['is_active' => $before],
            'after_values' => ['is_active' => ! $before],
            'created_at' => now(),
        ]);
    }

    public function dismissToken(): void
    {
        $this->newDeviceToken = null;
    }

    public function render()
    {
        $user = auth()->user();
        $branchIds = app(BranchAccess::class)->visibleBranchIds($user);
        return view('livewire.admin.printers.index', [
            'printers' => Printer::query()->whereIn('branch_id', $branchIds)->withCount([
                'jobs as pending_jobs_count' => fn ($query) => $query->whereIn('status', ['pending', 'processing']),
            ])->orderBy('name')->paginate(20),
            'branches' => $user->hasRole('system_admin') ? Branch::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'isSystemAdmin' => $user->hasRole('system_admin'),
        ])->layout('layouts.admin', ['heading' => 'إعداد الطابعات']);
    }
}
