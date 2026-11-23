<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductPriceHistory;
use App\Services\BranchAccess;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request, BranchAccess $branchAccess): JsonResponse
    {
        $query = Product::query()->with(['category:id,name', 'primaryImage']);

        if (! $request->user()->hasRole('system_admin')) {
            $query->whereIn('branch_id', $branchAccess->visibleBranchIds($request->user()));
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->input('category_id'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json($query->orderBy('sort_order')->orderBy('name')->paginate(30));
    }

    public function show(Request $request, Product $product, BranchAccess $branchAccess): JsonResponse
    {
        $branchAccess->assertCanAccess($request->user(), $product->branch_id);

        return response()->json([
            'product' => $product->load(['category', 'images', 'availabilityWindows', 'priceHistory']),
        ]);
    }

    public function store(Request $request, BranchAccess $branchAccess): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0.001', 'regex:/^\d+(\.\d{1,3})?$/'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0.001', 'regex:/^\d+(\.\d{1,3})?$/'],
            'is_active' => ['sometimes', 'boolean'],
            'is_best_seller' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,webp', 'max:3072'],
            'primary_image_index' => ['sometimes', 'integer', 'min:0'],
            'availability_windows' => ['sometimes', 'array'],
            'availability_windows.*.start_time' => ['required', 'date_format:H:i'],
            'availability_windows.*.end_time' => ['required', 'date_format:H:i'],
            'availability_windows.*.is_active' => ['sometimes', 'boolean'],
        ]);

        $branchId = (int) $data['branch_id'];
        $branchAccess->assertCanAccess($request->user(), $branchId);
        $this->assertCategoryBelongsToBranch((int) $data['category_id'], $branchId);
        $this->validateAvailabilityWindows($data['availability_windows'] ?? []);

        $images = $data['images'];
        $primaryIndex = (int) ($data['primary_image_index'] ?? 0);
        if ($primaryIndex >= count($images)) {
            throw ValidationException::withMessages(['primary_image_index' => ['اختيار الصورة الأساسية غير صالح.']]);
        }

        $product = DB::transaction(function () use ($data, $images, $primaryIndex, $branchId, $request): Product {
            $product = Product::query()->create([
                'branch_id' => $branchId,
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name'], $branchId),
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'compare_at_price' => $data['compare_at_price'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_best_seller' => $data['is_best_seller'] ?? false,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            foreach ($images as $index => $image) {
                $this->storeImage($product, $image, $index === $primaryIndex, $index);
            }

            foreach ($data['availability_windows'] ?? [] as $index => $window) {
                $product->availabilityWindows()->create([
                    'start_time' => $window['start_time'],
                    'end_time' => $window['end_time'],
                    'is_active' => $window['is_active'] ?? true,
                    'sort_order' => $index,
                ]);
            }

            AuditLog::query()->create([
                'branch_id' => $branchId,
                'actor_id' => $request->user()->id,
                'action' => 'product.created',
                'auditable_type' => Product::class,
                'auditable_id' => $product->id,
                'after_values' => $product->only(['name', 'slug', 'category_id', 'price', 'is_active']),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'created_at' => now(),
            ]);

            return $product;
        });

        return response()->json([
            'message' => 'تم إنشاء الصنف ونشره.',
            'product' => $product->load(['category', 'images', 'availabilityWindows']),
        ], 201);
    }

    public function update(Request $request, Product $product, BranchAccess $branchAccess): JsonResponse
    {
        $branchAccess->assertCanAccess($request->user(), $product->branch_id);

        $data = $request->validate([
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'required', 'string', 'max:180'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0.001', 'regex:/^\d+(\.\d{1,3})?$/'],
            'compare_at_price' => ['sometimes', 'nullable', 'numeric', 'min:0.001', 'regex:/^\d+(\.\d{1,3})?$/'],
            'is_active' => ['sometimes', 'boolean'],
            'is_best_seller' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'reason' => ['sometimes', 'required', 'string', 'max:500'],
            'images' => ['sometimes', 'array', 'min:1'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,webp', 'max:3072'],
            'primary_image_index' => ['sometimes', 'integer', 'min:0'],
            'primary_image_id' => ['sometimes', 'integer', 'exists:product_images,id'],
            'availability_windows' => ['sometimes', 'array'],
            'availability_windows.*.start_time' => ['required', 'date_format:H:i'],
            'availability_windows.*.end_time' => ['required', 'date_format:H:i'],
            'availability_windows.*.is_active' => ['sometimes', 'boolean'],
        ]);

        $categoryId = (int) ($data['category_id'] ?? $product->category_id);
        $this->assertCategoryBelongsToBranch($categoryId, (int) $product->branch_id);
        $this->validateAvailabilityWindows($data['availability_windows'] ?? []);

        $oldPrice = (string) $product->price;
        $priceChanged = array_key_exists('price', $data) && bccomp($oldPrice, (string) $data['price'], 3) !== 0;
        if ($priceChanged && empty($data['reason'])) {
            throw ValidationException::withMessages(['reason' => ['سبب تعديل السعر مطلوب.']]);
        }

        if (isset($data['primary_image_id'])) {
            $selectedImage = $product->images()->whereKey($data['primary_image_id'])->first();
            if (! $selectedImage) {
                throw ValidationException::withMessages(['primary_image_id' => ['الصورة لا تتبع هذا الصنف.']]);
            }
        }

        $newImages = $data['images'] ?? [];
        $primaryIndex = (int) ($data['primary_image_index'] ?? 0);
        if ($newImages !== [] && $primaryIndex >= count($newImages)) {
            throw ValidationException::withMessages(['primary_image_index' => ['اختيار الصورة الأساسية غير صالح.']]);
        }

        if ($product->images()->count() === 0 && $newImages === []) {
            throw ValidationException::withMessages(['images' => ['يجب أن يحتوي الصنف على صورة أساسية واحدة على الأقل.']]);
        }

        $before = $product->only(['name', 'category_id', 'price', 'compare_at_price', 'is_active', 'is_best_seller']);

        DB::transaction(function () use (
            $product,
            $data,
            $categoryId,
            $priceChanged,
            $oldPrice,
            $newImages,
            $primaryIndex,
            $before,
            $request
        ): void {
            $product->fill(collect($data)->only([
                'name', 'description', 'price', 'compare_at_price', 'is_active', 'is_best_seller', 'sort_order',
            ])->all());
            $product->category_id = $categoryId;
            $product->save();

            if ($priceChanged) {
                ProductPriceHistory::query()->create([
                    'product_id' => $product->id,
                    'branch_id' => $product->branch_id,
                    'changed_by' => $request->user()->id,
                    'old_price' => $oldPrice,
                    'new_price' => $product->price,
                    'reason' => $data['reason'],
                    'created_at' => now(),
                ]);
            }

            if ($newImages !== []) {
                foreach ($newImages as $index => $image) {
                    $this->storeImage($product, $image, $index === $primaryIndex && ! isset($data['primary_image_id']), $product->images()->count() + $index);
                }
            }

            if (isset($data['primary_image_id'])) {
                $product->images()->update(['is_primary' => false]);
                $product->images()->whereKey($data['primary_image_id'])->update(['is_primary' => true]);
            } elseif ($newImages !== [] && ! $product->images()->where('is_primary', true)->exists()) {
                $product->images()->orderBy('sort_order')->first()?->update(['is_primary' => true]);
            }

            if (array_key_exists('availability_windows', $data)) {
                $product->availabilityWindows()->delete();
                foreach ($data['availability_windows'] as $index => $window) {
                    $product->availabilityWindows()->create([
                        'start_time' => $window['start_time'],
                        'end_time' => $window['end_time'],
                        'is_active' => $window['is_active'] ?? true,
                        'sort_order' => $index,
                    ]);
                }
            }

            AuditLog::query()->create([
                'branch_id' => $product->branch_id,
                'actor_id' => $request->user()->id,
                'action' => 'product.updated',
                'auditable_type' => Product::class,
                'auditable_id' => $product->id,
                'before_values' => $before,
                'after_values' => $product->only(['name', 'category_id', 'price', 'compare_at_price', 'is_active', 'is_best_seller']),
                'reason' => $priceChanged ? $data['reason'] : null,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'created_at' => now(),
            ]);
        });

        return response()->json([
            'message' => 'تم تحديث الصنف.',
            'product' => $product->fresh()->load(['category', 'images', 'availabilityWindows', 'priceHistory']),
        ]);
    }

    private function assertCategoryBelongsToBranch(int $categoryId, int $branchId): void
    {
        abort_unless(
            Category::query()->whereKey($categoryId)->where('branch_id', $branchId)->exists(),
            422,
            'القسم المحدد لا يتبع الفرع.',
        );
    }

    private function validateAvailabilityWindows(array $windows): void
    {
        foreach ($windows as $index => $window) {
            if (Carbon::createFromFormat('H:i', $window['end_time'])->lessThanOrEqualTo(
                Carbon::createFromFormat('H:i', $window['start_time']),
            )) {
                throw ValidationException::withMessages([
                    "availability_windows.{$index}.end_time" => ['وقت النهاية يجب أن يكون بعد وقت البداية.'],
                ]);
            }
        }
    }

    private function uniqueSlug(string $name, int $branchId): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'product';
        $slug = $base;
        $suffix = 2;

        while (Product::withTrashed()->where('branch_id', $branchId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function storeImage(Product $product, $image, bool $isPrimary, int $sortOrder): ProductImage
    {
        if ($isPrimary) {
            $product->images()->update(['is_primary' => false]);
        }

        $path = $image->store('products/'.$product->id, 'public');

        return $product->images()->create([
            'path' => $path,
            'original_name' => substr($image->getClientOriginalName(), 0, 255),
            'mime_type' => $image->getMimeType(),
            'size_bytes' => $image->getSize(),
            'is_primary' => $isPrimary,
            'sort_order' => $sortOrder,
        ]);
    }
}
