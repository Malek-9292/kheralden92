<?php

namespace App\Livewire\Admin\Settings;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\Category;
use App\Models\Product;
use App\Services\BranchAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Index extends Component
{
    use WithFileUploads;

    public ?int $branchId = null;

    public array $featuredProductsByCategory = [];

    public array $categoryProductOptions = [];

    public string $restaurantName = '';

    public string $tagline = '';

    public string $phone = '';

    public string $whatsapp = '';

    public string $primaryColor = '#2f704b';

    public string $accentColor = '#8fcf9b';

    public string $brandBoxBackground = '#ffffff';

    public string $brandBoxBorderColor = '#e7eee8';

    public string $brandNameColor = '#1d5b3d';

    public string $brandTaglineColor = '#6f8376';

    public string $brandLogoBackground = '#f3f7f3';

    public string $bannerText = '';

    public string $bannerTextColor = '#ffffff';

    public string $bannerBackground = '#2f704b';

    public string $footerText = '';

    public int $bannerFontSize = 18;

    public bool $showStorefrontTopBlock = false;

    public bool $deliveryPaused = false;

    public string $deliveryPauseReason = '';

    public $logo = null;

    public ?string $currentLogoUrl = null;

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('settings.manage'), 403);
        $this->branchId = $user->hasRole('system_admin')
            ? Branch::query()->orderBy('id')->value('id')
            : $user->branch_id;
        $this->loadSettings();
    }

    public function updatedBranchId(): void
    {
        $this->loadSettings();
    }

    public function loadSettings(): void
    {
        if (! $this->branchId) {
            return;
        }

        app(BranchAccess::class)->assertCanAccess(auth()->user(), (int) $this->branchId);
        $settings = BranchSetting::query()->where('branch_id', $this->branchId)->pluck('value', 'key');

        $this->restaurantName = $settings->get('restaurant_name', Branch::find($this->branchId)?->name ?? '');
        $this->tagline = $settings->get('tagline', 'طعم يجمعنا');
        $this->phone = $settings->get('phone', '');
        $this->whatsapp = $settings->get('whatsapp', '');
        $this->primaryColor = $settings->get('primary_color', '#2f704b');
        $this->accentColor = $settings->get('accent_color', '#8fcf9b');
        $this->brandBoxBackground = $settings->get('brand_box_background', '#ffffff');
        $this->brandBoxBorderColor = $settings->get('brand_box_border_color', '#e7eee8');
        $this->brandNameColor = $settings->get('brand_name_color', '#1d5b3d');
        $this->brandTaglineColor = $settings->get('brand_tagline_color', '#6f8376');
        $this->brandLogoBackground = $settings->get('brand_logo_background', '#f3f7f3');
        $this->bannerText = $settings->get('banner_text', '');
        $this->bannerTextColor = $settings->get('banner_text_color', '#ffffff');
        $this->bannerBackground = $settings->get('banner_background', '#2f704b');
        $this->bannerFontSize = (int) $settings->get('banner_font_size', 18);
        $this->footerText = $settings->get('footer_text', '');
        $this->showStorefrontTopBlock = filter_var($settings->get('show_storefront_top_block', '0'), FILTER_VALIDATE_BOOLEAN);
        $this->deliveryPaused = filter_var($settings->get('delivery_paused', '0'), FILTER_VALIDATE_BOOLEAN);
        $this->deliveryPauseReason = (string) $settings->get('delivery_pause_reason', '');
        $logoPath = $settings->get('logo_path');
        $this->currentLogoUrl = $logoPath ? Storage::disk('public')->url($logoPath) : null;
        $savedFeatured = json_decode((string) $settings->get('storefront_featured_products', '{}'), true);
        $this->featuredProductsByCategory = is_array($savedFeatured) ? $savedFeatured : [];
        $this->categoryProductOptions = Category::query()
            ->where('branch_id', $this->branchId)
            ->where('is_active', true)
            ->with(['products' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('name')->select(['id', 'category_id', 'name'])])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'products' => $category->products->map(fn (Product $product) => ['id' => $product->id, 'name' => $product->name])->all(),
            ])->all();
        $this->logo = null;
    }

    public function save(): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('settings.manage'), 403);
        $branchId = $user->hasRole('system_admin') ? (int) $this->branchId : (int) $user->branch_id;
        app(BranchAccess::class)->assertCanAccess($user, $branchId);

        $data = $this->validate([
            'branchId' => ['required', 'integer', 'exists:branches,id'],
            'restaurantName' => ['required', 'string', 'max:150'],
            'tagline' => ['nullable', 'string', 'max:250'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'primaryColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accentColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brandBoxBackground' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brandBoxBorderColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brandNameColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brandTaglineColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brandLogoBackground' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'bannerText' => ['nullable', 'string', 'max:500'],
            'bannerTextColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'bannerBackground' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'bannerFontSize' => ['required', 'integer', 'min:12', 'max:48'],
            'footerText' => ['nullable', 'string', 'max:500'],
            'showStorefrontTopBlock' => ['boolean'],
            'deliveryPaused' => ['boolean'],
            'deliveryPauseReason' => ['nullable', 'string', 'max:250'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'featuredProductsByCategory' => ['array'],
            'featuredProductsByCategory.*' => ['array', 'max:3'],
            'featuredProductsByCategory.*.*' => ['integer'],
        ]);

        $keys = [
            'restaurant_name' => $data['restaurantName'],
            'tagline' => $data['tagline'] ?? '',
            'phone' => $data['phone'] ?? '',
            'whatsapp' => $data['whatsapp'] ?? '',
            'primary_color' => $data['primaryColor'],
            'accent_color' => $data['accentColor'],
            'brand_box_background' => $data['brandBoxBackground'],
            'brand_box_border_color' => $data['brandBoxBorderColor'],
            'brand_name_color' => $data['brandNameColor'],
            'brand_tagline_color' => $data['brandTaglineColor'],
            'brand_logo_background' => $data['brandLogoBackground'],
            'banner_text' => $data['bannerText'] ?? '',
            'banner_text_color' => $data['bannerTextColor'],
            'banner_background' => $data['bannerBackground'],
            'banner_font_size' => (string) $data['bannerFontSize'],
            'footer_text' => $data['footerText'] ?? '',
            'show_storefront_top_block' => ! empty($data['showStorefrontTopBlock']) ? '1' : '0',
            'delivery_paused' => ! empty($data['deliveryPaused']) ? '1' : '0',
            'delivery_pause_reason' => trim((string) ($data['deliveryPauseReason'] ?? '')),
            'storefront_featured_products' => json_encode($this->validatedFeaturedProducts($branchId), JSON_UNESCAPED_UNICODE),
        ];

        if ($data['logo']) {
            $keys['logo_path'] = $data['logo']->store('branches/'.$branchId.'/branding', 'public');
        }

        DB::transaction(function () use ($keys, $branchId, $user): void {
            $before = BranchSetting::query()->where('branch_id', $branchId)->pluck('value', 'key')->all();

            foreach ($keys as $key => $value) {
                $group = in_array($key, ['delivery_paused', 'delivery_pause_reason', 'show_storefront_top_block'], true)
                    ? 'operations'
                    : 'branding';

                BranchSetting::query()->updateOrCreate(
                    ['branch_id' => $branchId, 'key' => $key],
                    ['setting_group' => $group, 'value' => $value],
                );
            }

            AuditLog::query()->create([
                'branch_id' => $branchId,
                'actor_id' => $user->id,
                'action' => 'branch.settings.updated',
                'auditable_type' => BranchSetting::class,
                'auditable_id' => $branchId,
                'before_values' => $before,
                'after_values' => $keys,
                'reason' => (($before['delivery_paused'] ?? '0') !== $keys['delivery_paused'])
                    ? ($keys['delivery_paused'] === '1' ? 'تفعيل إيقاف التوصيل' : 'إلغاء إيقاف التوصيل')
                    : null,
                'created_at' => now(),
            ]);
        });

        $this->loadSettings();
        session()->flash('status', 'تم حفظ إعدادات المطعم.');
    }

    private function validatedFeaturedProducts(int $branchId): array
    {
        $result = [];
        $categories = Category::query()->where('branch_id', $branchId)->where('is_active', true)->pluck('id')->all();
        foreach ($this->featuredProductsByCategory as $categoryId => $productIds) {
            if (! in_array((int) $categoryId, $categories, true) || ! is_array($productIds)) {
                continue;
            }
            $ids = array_values(array_unique(array_map('intval', $productIds)));
            if (count($ids) > 3) {
                throw \\Illuminate\\Validation\\ValidationException::withMessages([
                    "featuredProductsByCategory.$categoryId" => 'يمكن اختيار 3 أصناف كحد أقصى لكل قسم.',
                ]);
            }
            $validIds = Product::query()
                ->where('branch_id', $branchId)
                ->where('category_id', (int) $categoryId)
                ->where('is_active', true)
                ->whereIn('id', $ids)
                ->pluck('id')->all();
            if (count($validIds) !== count($ids)) {
                throw \\Illuminate\\Validation\\ValidationException::withMessages([
                    "featuredProductsByCategory.$categoryId" => 'يوجد صنف غير تابع لهذا القسم.',
                ]);
            }
            $result[(string) $categoryId] = $validIds;
        }
        return $result;
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.admin.settings.index', [
            'branches' => $user->hasRole('system_admin') ? Branch::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'isSystemAdmin' => $user->hasRole('system_admin'),
        ])->layout('layouts.admin', ['heading' => 'إعدادات المطعم والهوية']);
    }
}
