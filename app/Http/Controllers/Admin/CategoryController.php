<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Services\BranchAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function index(Request $request, BranchAccess $branchAccess): JsonResponse
    {
        $query = Category::query()->with('parent:id,name')->orderBy('sort_order')->orderBy('name');

        if (! $request->user()->hasRole('system_admin')) {
            $query->whereIn('branch_id', $branchAccess->visibleBranchIds($request->user()));
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        return response()->json(['categories' => $query->get()]);
    }

    public function store(Request $request, BranchAccess $branchAccess): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $branchId = (int) $data['branch_id'];
        $branchAccess->assertCanAccess($request->user(), $branchId);
        $this->assertParentBelongsToBranch($data['parent_id'] ?? null, $branchId);

        $category = DB::transaction(function () use ($data, $branchId, $request): Category {
            $category = Category::query()->create([
                'branch_id' => $branchId,
                'parent_id' => $data['parent_id'] ?? null,
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name'], $branchId),
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);

            AuditLog::query()->create([
                'branch_id' => $branchId,
                'actor_id' => $request->user()->id,
                'action' => 'category.created',
                'auditable_type' => Category::class,
                'auditable_id' => $category->id,
                'after_values' => $category->only(['name', 'slug', 'parent_id', 'is_active', 'sort_order']),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'created_at' => now(),
            ]);

            return $category;
        });

        return response()->json(['category' => $category->load('parent')], 201);
    }

    public function update(Request $request, Category $category, BranchAccess $branchAccess): JsonResponse
    {
        $branchAccess->assertCanAccess($request->user(), $category->branch_id);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $parentId = array_key_exists('parent_id', $data) ? $data['parent_id'] : $category->parent_id;
        $this->assertParentBelongsToBranch($parentId, (int) $category->branch_id, $category->id);

        $before = $category->only(['name', 'slug', 'parent_id', 'is_active', 'sort_order']);

        DB::transaction(function () use ($category, $data, $before, $request): void {
            $category->fill(collect($data)->only(['name', 'parent_id', 'sort_order', 'is_active'])->all());
            $category->save();

            AuditLog::query()->create([
                'branch_id' => $category->branch_id,
                'actor_id' => $request->user()->id,
                'action' => 'category.updated',
                'auditable_type' => Category::class,
                'auditable_id' => $category->id,
                'before_values' => $before,
                'after_values' => $category->only(['name', 'slug', 'parent_id', 'is_active', 'sort_order']),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'created_at' => now(),
            ]);
        });

        return response()->json(['category' => $category->fresh()->load('parent')]);
    }

    public function destroy(Request $request, Category $category, BranchAccess $branchAccess): JsonResponse
    {
        $branchAccess->assertCanAccess($request->user(), $category->branch_id);

        if ($category->children()->exists() || $category->products()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف القسم قبل نقل أو معالجة الأقسام الفرعية والأصناف المرتبطة به.',
            ], 409);
        }

        $category->delete();

        AuditLog::query()->create([
            'branch_id' => $category->branch_id,
            'actor_id' => $request->user()->id,
            'action' => 'category.deleted',
            'auditable_type' => Category::class,
            'auditable_id' => $category->id,
            'before_values' => $category->only(['name', 'slug', 'parent_id', 'is_active']),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'created_at' => now(),
        ]);

        return response()->json(['message' => 'تم حذف القسم.']);
    }

    private function assertParentBelongsToBranch(?int $parentId, int $branchId, ?int $categoryId = null): void
    {
        if ($parentId === null) {
            return;
        }

        $parent = Category::query()->whereKey($parentId)->where('branch_id', $branchId)->first();

        if (! $parent || $parent->id === $categoryId) {
            throw ValidationException::withMessages(['parent_id' => ['القسم الأب غير صالح لهذا الفرع.']]);
        }

        $ancestor = $parent;
        while ($ancestor) {
            if ($ancestor->id === $categoryId) {
                throw ValidationException::withMessages(['parent_id' => ['لا يمكن نقل القسم إلى أحد أبنائه.']]);
            }
            $ancestor = $ancestor->parent;
        }
    }

    private function uniqueSlug(string $name, int $branchId): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'category';
        $slug = $base;
        $suffix = 2;

        while (Category::withTrashed()->where('branch_id', $branchId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
