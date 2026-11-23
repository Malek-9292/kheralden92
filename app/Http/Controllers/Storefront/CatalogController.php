<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function categories(string $branchCode): JsonResponse
    {
        $branch = $this->activeBranch($branchCode);

        $categories = Category::query()
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'slug', 'sort_order']);

        return response()->json([
            'branch' => ['code' => $branch->code, 'name' => $branch->name],
            'categories' => $categories,
        ]);
    }

    public function products(Request $request, string $branchCode): JsonResponse
    {
        $branch = $this->activeBranch($branchCode);
        $now = now($branch->timezone)->format('H:i:s');

        $query = Product::query()
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->whereHas('category', fn ($categories) => $categories->where('is_active', true))
            ->where(function ($availability) use ($now): void {
                $availability
                    ->whereDoesntHave('availabilityWindows')
                    ->orWhereHas('availabilityWindows', function ($windows) use ($now): void {
                        $windows->where('is_active', true)
                            ->where('start_time', '<=', $now)
                            ->where('end_time', '>', $now);
                    });
            })
            ->with([
                'category:id,name,slug',
                'primaryImage:id,product_id,path,original_name,mime_type,size_bytes,is_primary,sort_order',
                'images:id,product_id,path,original_name,mime_type,size_bytes,is_primary,sort_order',
                'availabilityWindows:id,product_id,start_time,end_time,is_active,sort_order',
            ]);

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->query('category_id'));
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->query('search'));
            if ($term !== '') {
                $query->where(function ($search) use ($term): void {
                    $search->where('name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            }
        }

        return response()->json([
            'branch' => ['code' => $branch->code, 'name' => $branch->name],
            'products' => $query
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(min(max((int) $request->query('per_page', 24), 1), 60)),
        ]);
    }

    public function show(string $branchCode, string $slug): JsonResponse
    {
        $branch = $this->activeBranch($branchCode);
        $now = now($branch->timezone)->format('H:i:s');

        $product = Product::query()
            ->where('branch_id', $branch->id)
            ->where('slug', $slug)
            ->where('is_active', true)
            ->whereHas('category', fn ($categories) => $categories->where('is_active', true))
            ->where(function ($availability) use ($now): void {
                $availability
                    ->whereDoesntHave('availabilityWindows')
                    ->orWhereHas('availabilityWindows', function ($windows) use ($now): void {
                        $windows->where('is_active', true)
                            ->where('start_time', '<=', $now)
                            ->where('end_time', '>', $now);
                    });
            })
            ->with([
                'category:id,name,slug',
                'images:id,product_id,path,original_name,mime_type,size_bytes,is_primary,sort_order',
            ])
            ->firstOrFail();

        return response()->json([
            'branch' => ['code' => $branch->code, 'name' => $branch->name],
            'product' => $product,
        ]);
    }

    private function activeBranch(string $branchCode): Branch
    {
        return Branch::query()
            ->where('code', strtoupper($branchCode))
            ->where('is_active', true)
            ->firstOrFail();
    }
}
