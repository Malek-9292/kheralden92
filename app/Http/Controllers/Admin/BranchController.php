<?php

namespace App\\Http\\Controllers\\Admin;

use App\\Http\\Controllers\\Controller;
use App\\Models\\AuditLog;
use App\\Models\\Branch;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Validation\\Rule;

class BranchController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'branches' => Branch::query()
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->paginate(25),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/', 'unique:branches,code'],
            'name' => ['required', 'string', 'max:150'],
            'timezone' => ['sometimes', 'required', 'timezone'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $branch = DB::transaction(function () use ($data, $request): Branch {
            $branch = Branch::query()->create([
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
                'timezone' => $data['timezone'] ?? 'Asia/Kuwait',
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->audit($request, $branch, 'branch.created', null, $branch->only([
                'code', 'name', 'timezone', 'is_active',
            ]));

            return $branch;
        });

        return response()->json([
            'message' => 'تم إنشاء الفرع.',
            'branch' => $branch,
        ], 201);
    }

    public function update(Request $request, Branch $branch): JsonResponse
    {
        $data = $request->validate([
            'code' => [
                'sometimes', 'required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('branches', 'code')->ignore($branch->id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'timezone' => ['sometimes', 'required', 'timezone'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $before = $branch->only(['code', 'name', 'timezone', 'is_active']);

        DB::transaction(function () use ($branch, $data, $before, $request): void {
            $branch->fill($data);

            if (array_key_exists('code', $data)) {
                $branch->code = strtoupper($data['code']);
            }

            $branch->save();

            $this->audit(
                $request,
                $branch,
                'branch.updated',
                $before,
                $branch->only(['code', 'name', 'timezone', 'is_active']),
            );
        });

        return response()->json([
            'message' => 'تم تحديث بيانات الفرع.',
            'branch' => $branch->fresh(),
        ]);
    }

    private function audit(
        Request $request,
        Branch $branch,
        string $action,
        ?array $before,
        array $after,
    ): void {
        AuditLog::query()->create([
            'branch_id' => $branch->id,
            'actor_id' => $request->user()->id,
            'action' => $action,
            'auditable_type' => Branch::class,
            'auditable_id' => $branch->id,
            'before_values' => $before,
            'after_values' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'created_at' => now(),
        ]);
    }
}
