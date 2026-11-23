<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', 'distinct', 'exists:roles,id'],
        ]);

        $branchAccess->assertCanAccess($request->user(), (int) $data['branch_id']);
        $roleIds = $data['role_ids'] ?? [];
        $this->assertRolesAllowed($roleIds, (int) $data['branch_id'], $request->user()->hasRole('system_admin'));

        $employee = DB::transaction(function () use ($data, $roleIds): User {
            $employee = User::query()->create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'branch_id' => $data['branch_id'],
                'status' => $data['status'] ?? 'active',
            ]);
            $employee->roles()->sync($roleIds);

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
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', 'distinct', 'exists:roles,id'],
        ]);

        $newBranchId = (int) ($data['branch_id'] ?? $oldBranchId);
        $branchAccess->assertCanAccess($request->user(), $newBranchId);
        $roleIds = $data['role_ids'] ?? $employee->roles()->pluck('roles.id')->all();
        $this->assertRolesAllowed($roleIds, $newBranchId, $request->user()->hasRole('system_admin'));

        $mustRevokeSessions = (array_key_exists('status', $data) && $data['status'] !== 'active') || $newBranchId !== $oldBranchId;

        DB::transaction(function () use ($employee, $data, $roleIds, $newBranchId, $mustRevokeSessions): void {
            $employee->fill(collect($data)->only(['name', 'phone', 'status'])->all());
            $employee->branch_id = $newBranchId;

            if (isset($data['password'])) {
                $employee->password = Hash::make($data['password']);
            }

            $employee->save();
            $employee->roles()->sync($roleIds);

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
