<?php

namespace App\Livewire\Admin\Catalog;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductPriceHistory;
use App\Services\BranchAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Products extends Component
{
    use WithFileUploads, WithPagination;

    #[Url]
    public string $search = '';

    public string $categoryFilter = '';

    public string $statusFilter = '';

    public ?int $branchId = null;

    public ?int $editingId = null;

    public ?int $categoryId = null;

    public string $name = '';

    public string $description = '';

    public string $breadType = '';

    public string $ingredients = '';

    public string $price = '';

    public string $compareAtPrice = '';

    public string $reason = '';

    public bool $isActive = true;

    public bool $isBestSeller = false;

    public int $sortOrder = 0;

    public array $images = [];

    public int $primaryImageIndex = 0;

    /** @var array<int, array{start_time:string,end_time:string,is_active:bool}> */
    public array $availabilityWindows = [];

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

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
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
            'branchId' => ['required', 'integer', 'exists:branches,id'],
            'categoryId' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'breadType' => ['nullable', 'in:samoon,saj,none'],
            'ingredients' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0.001', 'regex:/^\d+(\.\d{1,3})?$/'],
            'compareAtPrice' => ['nullable', 'numeric', 'min:0.001', 'regex:/^\d+(\.\d{1,3})?$/'],
            'reason' => ['nullable', 'string', 'max:500'],
            'isActive' => ['boolean'],
            'isBestSeller' => ['boolean'],
            'sortOrder' => ['integer', 'min:0'],
            'images' => [$this->editingId ? 'nullable' : 'required', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,webp', 'max:3072'],
            'primaryImageIndex' => ['integer', 'min:0'],
            'availabilityWindows' => ['array'],
            'availabilityWindows.*.start_time' => ['required', 'date_format:H:i'],
            'availabilityWindows.*.end_time' => ['required', 'date_format:H:i'],
            'availabilityWindows.*.is_active' => ['boolean'],
        ]);

        foreach ($data['availabilityWindows'] ?? [] as $index => $window) {
            if (($window['end_time'] ?? '') <= ($window['start_time'] ?? '')) {
                throw ValidationException::withMessages([
                    "availabilityWindows.{$index}.end_time" => ['وقت النهاية يجب أن يكون بعد وقت البداية.'],
                ]);
            }
        }

        abort_unless(
            Category::query()->whereKey($data['categoryId'])->where('branch_id', $branchId)->exists(),
            422,
            'القسم المحدد لا يتبع هذا الفرع.',
        );

        $product = $this->editingId ? Product::query()->findOrFail($this->editingId) : null;
        if ($product) {
            app(BranchAccess::class)->assertCanAccess($user, $product->branch_id);
            abort_unless($product->branch_id === $branchId, 422);
            if ($product->images()->count() === 0 && count($data['images'] ?? []) === 0) {
                throw ValidationException::withMessages(['images' => ['يجب إضافة صورة واحدة على الأقل للصنف.']]);
            }
        } elseif (count($data['images'] ?? []) === 0) {
            throw ValidationException::withMessages(['images' => ['أضف صورة واحدة على الأقل للصنف.']]);
        }

        if (count($data['images'] ?? []) > 0 && $data['primaryImageIndex'] >= count($data['images'])) {
            throw ValidationException::withMessages(['primaryImageIndex' => ['اختيار الصورة الأساسية غير صالح.']]);
        }

        $oldPrice = $product ? (string) $product->price : null;
        $priceChanged = $product && $this->normalizePrice($oldPrice) !== $this->normalizePrice($data['price']);
        if ($priceChanged && trim($data['reason'] ?? '') === '') {
            throw ValidationException::withMessages(['reason' => ['سبب تعديل السعر مطلوب.']]);
        }

        DB::transaction(function () use ($data, $branchId, $user, $product, $oldPrice, $priceChanged): void {
            $before = $product?->only(['name', 'category_id', 'price', 'compare_at_price', 'bread_type', 'ingredients', 'is_active', 'is_best_seller']);

            if (! $product) {
                $product = new Product(['branch_id' => $branchId]);
                $product->slug = $this->uniqueSlug($data['name'], $branchId);
            }

            $product->fill([
                'branch_id' => $branchId,
                'category_id' => $data['categoryId'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'bread_type' => ($data['breadType'] ?? '') !== '' ? $data['breadType'] : null,
                'ingredients' => $data['ingredients'] ?: null,
                'price' => $data['price'],
                'compare_at_price' => $data['compareAtPrice'] ?: null,
                'is_active' => $data['isActive'],
                'is_best_seller' => $data['isBestSeller'],
                'sort_order' => $data['sortOrder'],
            ])->save();

            if ($priceChanged) {
                ProductPriceHistory::query()->create([
                    'product_id' => $product->id,
                    'branch_id' => $branchId,
                    'changed_by' => $user->id,
                    'old_price' => $oldPrice,
                    'new_price' => $product->price,
                    'reason' => $data['reason'],
                    'created_at' => now(),
                ]);
            }

            foreach ($data['images'] ?? [] as $index => $image) {
                if ($index === (int) $data['primaryImageIndex']) {
                    $product->images()->update(['is_primary' => false]);
                }
                $path = $image->store('products/'.$product->id, 'public');
                $product->images()->create([
                    'path' => $path,
                    'original_name' => substr($image->getClientOriginalName(), 0, 255),
                    'mime_type' => $image->getMimeType(),
                    'size_bytes' => $image->getSize(),
                    'is_primary' => $index === (int) $data['primaryImageIndex'] && ! $product->images()->where('is_primary', true)->exists(),
                    'sort_order' => $product->images()->count(),
                ]);
            }

            if (! $product->images()->where('is_primary', true)->exists()) {
                $product->images()->orderBy('sort_order')->first()?->update(['is_primary' => true]);
            }

            $product->availabilityWindows()->delete();
            foreach ($data['availabilityWindows'] ?? [] as $index => $window) {
                $product->availabilityWindows()->create([
                    'start_time' => $window['start_time'],
                    'end_time' => $window['end_time'],
                    'is_active' => $window['is_active'] ?? true,
                    'sort_order' => $index,
                ]);
            }

            AuditLog::query()->create([
                'branch_id' => $branchId,
                'actor_id' => $user->id,
                'action' => $this->editingId ? 'product.updated' : 'product.created',
                'auditable_type' => Product::class,
                'auditable_id' => $product->id,
                'before_values' => $before,
                'after_values' => $product->only(['name', 'category_id', 'price', 'compare_at_price', 'bread_type', 'ingredients', 'is_active', 'is_best_seller']),
                'reason' => $priceChanged ? $data['reason'] : null,
                'created_at' => now(),
            ]);
        });

        $this->resetForm();
        session()->flash('status', 'تم حفظ الصنف بنجاح.');
    }

    public function addAvailabilityWindow(): void
    {
        $this->availabilityWindows[] = [
            'start_time' => '09:00',
            'end_time' => '14:00',
            'is_active' => true,
        ];
    }

    public function removeAvailabilityWindow(int $index): void
    {
        unset($this->availabilityWindows[$index]);
        $this->availabilityWindows = array_values($this->availabilityWindows);
    }

    public function edit(int $id): void
    {
        $product = Product::query()->with(['images', 'availabilityWindows'])->findOrFail($id);
        app(BranchAccess::class)->assertCanAccess(auth()->user(), $product->branch_id);
        $this->editingId = $product->id;
        $this->branchId = $product->branch_id;
        $this->categoryId = $product->category_id;
        $this->name = $product->name;
        $this->description = (string) $product->description;
        $this->breadType = (string) ($product->bread_type ?? '');
        $this->ingredients = (string) ($product->ingredients ?? '');
        $this->price = (string) $product->price;
        $this->compareAtPrice = (string) ($product->compare_at_price ?? '');
        $this->isActive = $product->is_active;
        $this->isBestSeller = $product->is_best_seller;
        $this->sortOrder = $product->sort_order;
        $this->images = [];
        $this->reason = '';
        $this->availabilityWindows = $product->availabilityWindows->map(fn ($window): array => [
            'start_time' => substr((string) $window->start_time, 0, 5),
            'end_time' => substr((string) $window->end_time, 0, 5),
            'is_active' => (bool) $window->is_active,
        ])->values()->all();
    }

    public function toggleActive(int $id): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('catalog.manage'), 403);
        $product = Product::query()->findOrFail($id);
        app(BranchAccess::class)->assertCanAccess($user, $product->branch_id);
        $before = $product->is_active;
        $product->update(['is_active' => ! $before]);
        AuditLog::query()->create([
            'branch_id' => $product->branch_id,
            'actor_id' => $user->id,
            'action' => 'product.status_updated',
            'auditable_type' => Product::class,
            'auditable_id' => $product->id,
            'before_values' => ['is_active' => $before],
            'after_values' => ['is_active' => ! $before],
            'created_at' => now(),
        ]);
    }

    public function toggleBestSeller(int $id): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('catalog.manage'), 403);
        $product = Product::query()->findOrFail($id);
        app(BranchAccess::class)->assertCanAccess($user, $product->branch_id);
        $before = $product->is_best_seller;
        $product->update(['is_best_seller' => ! $before]);
        AuditLog::query()->create([
            'branch_id' => $product->branch_id,
            'actor_id' => $user->id,
            'action' => 'product.best_seller_updated',
            'auditable_type' => Product::class,
            'auditable_id' => $product->id,
            'before_values' => ['is_best_seller' => $before],
            'after_values' => ['is_best_seller' => ! $before],
            'created_at' => now(),
        ]);
    }

    public function deleteImage(int $imageId): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('catalog.manage'), 403);
        $image = ProductImage::query()->findOrFail($imageId);
        $product = $image->product;
        app(BranchAccess::class)->assertCanAccess($user, $product->branch_id);
        abort_if($product->images()->count() <= 1, 409, 'لا يمكن حذف الصورة الوحيدة للصنف.');
        $wasPrimary = $image->is_primary;
        $path = $image->path;

        DB::transaction(function () use ($image, $product, $wasPrimary, $path, $user): void {
            $image->delete();
            if ($wasPrimary) {
                $product->images()->orderBy('sort_order')->first()?->update(['is_primary' => true]);
            }

            AuditLog::query()->create([
                'branch_id' => $product->branch_id,
                'actor_id' => $user->id,
                'action' => 'product.image_deleted',
                'auditable_type' => Product::class,
                'auditable_id' => $product->id,
                'before_values' => ['image_id' => $image->id, 'path' => $path, 'was_primary' => $wasPrimary],
                'created_at' => now(),
            ]);
        });

        Storage::disk('public')->delete($path);
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->breadType = '';
        $this->ingredients = '';
        $this->price = '';
        $this->compareAtPrice = '';
        $this->reason = '';
        $this->categoryId = null;
        $this->isActive = true;
        $this->isBestSeller = false;
        $this->sortOrder = 0;
        $this->images = [];
        $this->primaryImageIndex = 0;
        $this->availabilityWindows = [];
    }

    private function normalizePrice(string $price): string
    {
        [$whole, $fraction] = array_pad(explode('.', $price, 2), 2, '');

        return (ltrim($whole, '0') ?: '0').'.'.str_pad(substr($fraction, 0, 3), 3, '0');
    }

    private function uniqueSlug(string $name, int $branchId): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $suffix = 2;
        while (Product::withTrashed()->where('branch_id', $branchId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    public function render()
    {
        $user = auth()->user();
        $branchIds = app(BranchAccess::class)->visibleBranchIds($user);
        $query = Product::query()->whereIn('branch_id', $branchIds)->with(['category:id,name', 'primaryImage'])->orderBy('sort_order')->orderBy('name');
        if ($this->search !== '') {
            $term = trim($this->search);
            $query->where('name', 'like', '%'.$term.'%');
        }
        if ($this->categoryFilter !== '') {
            $query->where('category_id', $this->categoryFilter);
        }
        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        return view('livewire.admin.catalog.products', [
            'products' => $query->paginate(20),
            'editingProduct' => $this->editingId ? Product::query()->whereIn('branch_id', $branchIds)->with('images')->find($this->editingId) : null,
            'categories' => Category::query()->whereIn('branch_id', $branchIds)->orderBy('sort_order')->get(['id', 'name']),
            'formCategories' => Category::query()->whereIn('branch_id', $branchIds)->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
            'branches' => $user->hasRole('system_admin') ? Branch::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'isSystemAdmin' => $user->hasRole('system_admin'),
        ])->layout('layouts.admin', ['heading' => 'إدارة الأصناف']);
    }
}
