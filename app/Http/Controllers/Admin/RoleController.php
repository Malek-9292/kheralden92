<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Services\BranchAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    public function index(Request $request, BranchAccess $branchAccess): JsonResponse
    {
        $query = Role::query()->with('permissions')->where('slug', '!=', 'system_admin');

        if (! $request->user()->hasRole('system_admin')) {
            $branchIds = $branchAccess->visibleBranchIds($request->user());
            $query->where(function ($builder) use ($branchIds): void {
                $builder->whereNull('branch_id')->orWhereIn('branch_id', $branchIds);
            });
        }

        $permissions = Permission::query();
        if (! $request->user()->hasRole('system_admin')) {
            $permissions->where('slug', '!=', 'modules.manage');
        }

        return response()->json([
            'roles' => $query->orderBy('name')->get(),
            'permissions' => $permissions->orderBy('module')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, BranchAccess $branchAccess): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('roles', 'slug')->where(fn ($query) => $query->where('branch_id', $request->input('branch_id'))),
            ],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'permission_slugs' => ['sometimes', 'array'],
            'permission_slugs.*' => ['string', 'distinct', 'exists:permissions,slug'],
        ]);

        if ($request->user()->hasRole('system_admin') === false && in_array('modules.manage', $data['permission_slugs'] ?? [], true)) {
            throw ValidationException::withMessages([
                'permission_slugs' => ['صلاحية إدارة الوحدات مخصصة لمدير النظام فقط.'],
            ]);
        }

        $branchId = (int) $data['branch_id'];
        $branchAccess->assertCanAccess($request->user(), $branchId);

        $role = DB::transaction(function () use ($data, $branchId, $request): Role {
            $role = Role::query()->create([
                'branch_id' => $branchId,
                'name' => $data['name'],
                'slug' => $data['slug'],
                'guard_name' => 'web',
                'is_system' => false,
            ]);

            $permissionIds = Permission::query()
                ->whereIn('slug', $data['permission_slugs'] ?? [])
                ->pluck('id');
            $role->permissions()->sync($permissionIds);

            AuditLog::query()->create([
                'branch_id' => $branchId,
                'actor_id' => $request->user()->id,
                'action' => 'role.created',
                'auditable_type' => Role::class,
                'auditable_id' => $role->id,
                'after_values' => [
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'permission_slugs' => $data['permission_slugs'] ?? [],
                ],
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'created_at' => now(),
            ]);

            return $role;
        });

        return response()->json(['role' => $role->load('permissions')], 201);
    }

    public function update(Request $request, Role $role, BranchAccess $branchAccess): JsonResponse
    {
        abort_if($role->is_system || $role->slug === 'system_admin', 403, 'لا يمكن تعديل دور نظام أساسي.');

        $branchAccess->assertCanAccess($request->user(), (int) $role->branch_id);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('roles', 'slug')->where(fn ($query) => $query->where('branch_id', $role->branch_id))->ignore($role->id),
            ],
            'permission_slugs' => ['sometimes', 'array'],
            'permission_slugs.*' => ['string', 'distinct', 'exists:permissions,slug'],
        ]);

        if (! $request->user()->hasRole('system_admin') && in_array('modules.manage', $data['permission_slugs'] ?? [], true)) {
            throw ValidationException::withMessages([
                'permission_slugs' => ['صلاحية إدارة الوحدات مخصصة لمدير النظام فقط.'],
            ]);
        }

        $before = [
            'name' => $role->name,
            'slug' => $role->slug,
            'permission_slugs' => $role->permissions()->pluck('slug')->all(),
        ];

        DB::transaction(function () use ($role, $data, $before, $request): void {
            $role->fill(collect($data)->only(['name', 'slug'])->all())->save();

            if (array_key_exists('permission_slugs', $data)) {
                $permissionIds = Permission::query()
                    ->whereIn('slug', $data['permission_slugs'])
                    ->pluck('id');
                $role->permissions()->sync($permissionIds);
            }

            AuditLog::query()->create([
                'branch_id' => $role->branch_id,
                'actor_id' => $request->user()->id,
                'action' => 'role.updated',
                'auditable_type' => Role::class,
                'auditable_id' => $role->id,
                'before_values' => $before,
                'after_values' => [
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'permission_slugs' => $role->permissions()->pluck('slug')->all(),
                ],
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'created_at' => now(),
            ]);
        });

        return response()->json(['role' => $role->fresh()->load('permissions')]);
    }
}
