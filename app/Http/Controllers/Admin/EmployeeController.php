<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\BranchAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    public function index(Request $request, BranchAccess $branchAccess): JsonResponse
    {
        $query = User::query()->with(['branch', 'roles.permissions']);

        if (! $request->user()->hasRole('system_admin')) {
            $query->whereIn('branch_id', $branchAccess->visibleBranchIds($request->user()));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));
            $query->where(fn ($builder) => $builder
                ->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%"));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json($query->orderBy('name')->paginate(25));
    }

    public function store(Request $request, BranchAccess $branchAccess): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:12', 'max:200'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'status' => ['sometimes', Rule::in(['active', 'suspended', 'inactive'])],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'distinct', 'exists:roles,id'],
        ]);

        $branchId = (int) $data['branch_id'];
        $branchAccess->assertCanAccess($request->user(), $branchId);
        $roleIds = $data['role_ids'] ?? [];
        $this->assertRolesAllowed($roleIds, $branchId, $request->user()->hasRole('system_admin'));

        $employee = DB::transaction(function () use ($data, $roleIds, $branchId, $request): User {
            $employee = User::query()->create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'branch_id' => $branchId,
                'status' => $data['status'] ?? 'active',
                'valid_from' => $data['valid_from'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
            ]);
            $employee->roles()->sync($roleIds);

            AuditLog::query()->create([
                'branch_id' => $branchId,
                'actor_id' => $request->user()->id,
                'action' => 'employee.created',
                'auditable_type' => User::class,
                'auditable_id' => $employee->id,
                'after_values' => [
                    'name' => $employee->name,
                    'phone' => $employee->phone,
                    'branch_id' => $branchId,
                    'status' => $employee->status,
                    'valid_from' => $employee->valid_from,
                    'valid_until' => $employee->valid_until,
                    'role_ids' => $roleIds,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'created_at' => now(),
            ]);

            return $employee;
        });

        return response()->json([
            'message' => 'تم إنشاء حساب الموظف.',
            'employee' => $employee->load(['branch', 'roles.permissions']),
        ], 201);
    }

    public function update(Request $request, User $employee, BranchAccess $branchAccess): JsonResponse
    {
        abort_if($employee->hasRole('system_admin'), 403, 'لا يمكن تعديل حساب مدير النظام الرئيسي من إدارة الموظفين.');

        $oldBranchId = (int) $employee->branch_id;
        $branchAccess->assertCanAccess($request->user(), $oldBranchId);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'phone' => ['sometimes', 'required', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($employee->id)],
            'password' => ['sometimes', 'required', 'string', 'min:12', 'max:200'],
            'branch_id' => ['sometimes', 'required', 'integer', 'exists:branches,id'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'suspended', 'inactive'])],
            'valid_from' => ['sometimes', 'nullable', 'date'],
            'valid_until' => ['sometimes', 'nullable', 'date'],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', 'distinct', 'exists:roles,id'],
        ]);

        $newBranchId = (int) ($data['branch_id'] ?? $oldBranchId);
        $effectiveValidFrom = array_key_exists('valid_from', $data) ? $data['valid_from'] : $employee->valid_from;
        $effectiveValidUntil = array_key_exists('valid_until', $data) ? $data['valid_until'] : $employee->valid_until;
        if ($effectiveValidFrom && $effectiveValidUntil && now()->parse($effectiveValidUntil)->lt(now()->parse($effectiveValidFrom))) {
            throw ValidationException::withMessages(['valid_until' => ['يجب أن يكون تاريخ نهاية الصلاحية بعد تاريخ بدايتها.']]);
        }

        if ($newBranchId !== $oldBranchId && ! array_key_exists('role_ids', $data)) {
            throw ValidationException::withMessages([
                'role_ids' => ['حدد صلاحيات الموظف مرة أخرى عند نقله إلى فرع مختلف.'],
            ]);
        }

        $branchAccess->assertCanAccess($request->user(), $newBranchId);
        $roleIds = $data['role_ids'] ?? $employee->roles()->pluck('roles.id')->all();
        $this->assertRolesAllowed($roleIds, $newBranchId, $request->user()->hasRole('system_admin'));

        $mustRevokeSessions = (array_key_exists('status', $data) && $data['status'] !== 'active')
            || $newBranchId !== $oldBranchId
            || array_key_exists('valid_from', $data)
            || array_key_exists('valid_until', $data);

        $beforeValues = $employee->only(['name', 'phone', 'branch_id', 'status']);
        $beforeValues['role_ids'] = $employee->roles()->pluck('roles.id')->all();

        DB::transaction(function () use (
            $employee,
            $data,
            $roleIds,
            $newBranchId,
            $mustRevokeSessions,
            $beforeValues,
            $request
        ): void {
            $employee->fill(collect($data)->only(['name', 'phone', 'status', 'valid_from', 'valid_until'])->all());
            $employee->branch_id = $newBranchId;

            if (isset($data['password'])) {
                $employee->password = Hash::make($data['password']);
            }

            $employee->save();
            $employee->roles()->sync($roleIds);

            AuditLog::query()->create([
                'branch_id' => $newBranchId,
                'actor_id' => $request->user()->id,
                'action' => 'employee.updated',
                'auditable_type' => User::class,
                'auditable_id' => $employee->id,
                'before_values' => $beforeValues,
                'after_values' => [
                    'name' => $employee->name,
                    'phone' => $employee->phone,
                    'branch_id' => $employee->branch_id,
                    'status' => $employee->status,
                    'valid_from' => $employee->valid_from,
                    'valid_until' => $employee->valid_until,
                    'role_ids' => $roleIds,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'created_at' => now(),
            ]);

            if ($mustRevokeSessions) {
                $employee->employeeSessions()
                    ->whereNull('logged_out_at')
                    ->update(['logged_out_at' => now(), 'ended_reason' => 'account_changed']);
            }
        });

        return response()->json([
            'message' => 'تم تحديث حساب الموظف.',
            'employee' => $employee->fresh()->load(['branch', 'roles.permissions']),
        ]);
    }

    private function assertRolesAllowed(array $roleIds, int $branchId, bool $isSystemAdmin): void
    {
        if ($roleIds === []) {
            return;
        }

        $roles = Role::query()->whereIn('id', $roleIds)->get();

        if ($roles->count() !== count(array_unique($roleIds))) {
            throw ValidationException::withMessages(['role_ids' => ['بعض الأدوار المحددة غير موجودة.']]);
        }

        foreach ($roles as $role) {
            if ($role->slug === 'system_admin' && ! $isSystemAdmin) {
                throw ValidationException::withMessages(['role_ids' => ['لا يمكن منح صلاحية مدير النظام الرئيسي.']]);
            }

            if ($role->branch_id !== null && (int) $role->branch_id !== $branchId) {
                throw ValidationException::withMessages(['role_ids' => ['لا يمكن إسناد دور تابع لفرع آخر.']]);
            }
        }
    }
}
