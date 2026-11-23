<?php

namespace App\Livewire\Admin\Settings;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\BranchSetting;
use App\Services\BranchAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Index extends Component
{
    use WithFileUploads;

    public ?int $branchId = null;

    public string $restaurantName = '';

    public string $tagline = '';

    public string $phone = '';

    public string $whatsapp = '';

    public string $primaryColor = '#2f704b';

    public string $accentColor = '#8fcf9b';

    public string $bannerText = '';

    public string $bannerTextColor = '#ffffff';

    public string $bannerBackground = '#2f704b';

    public string $footerText = '';

    public int $bannerFontSize = 18;

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
        $this->bannerText = $settings->get('banner_text', '');
        $this->bannerTextColor = $settings->get('banner_text_color', '#ffffff');
        $this->bannerBackground = $settings->get('banner_background', '#2f704b');
        $this->bannerFontSize = (int) $settings->get('banner_font_size', 18);
        $this->footerText = $settings->get('footer_text', '');
        $this->deliveryPaused = filter_var($settings->get('delivery_paused', '0'), FILTER_VALIDATE_BOOLEAN);
        $this->deliveryPauseReason = (string) $settings->get('delivery_pause_reason', '');
        $logoPath = $settings->get('logo_path');
        $this->currentLogoUrl = $logoPath ? Storage::disk('public')->url($logoPath) : null;
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
            'bannerText' => ['nullable', 'string', 'max:500'],
            'bannerTextColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'bannerBackground' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'bannerFontSize' => ['required', 'integer', 'min:12', 'max:48'],
            'footerText' => ['nullable', 'string', 'max:500'],
            'deliveryPaused' => ['boolean'],
            'deliveryPauseReason' => ['nullable', 'string', 'max:250'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $keys = [
            'restaurant_name' => $data['restaurantName'],
            'tagline' => $data['tagline'] ?? '',
            'phone' => $data['phone'] ?? '',
            'whatsapp' => $data['whatsapp'] ?? '',
            'primary_color' => $data['primaryColor'],
            'accent_color' => $data['accentColor'],
            'banner_text' => $data['bannerText'] ?? '',
            'banner_text_color' => $data['bannerTextColor'],
            'banner_background' => $data['bannerBackground'],
            'banner_font_size' => (string) $data['bannerFontSize'],
            'footer_text' => $data['footerText'] ?? '',
            'delivery_paused' => ! empty($data['deliveryPaused']) ? '1' : '0',
            'delivery_pause_reason' => trim((string) ($data['deliveryPauseReason'] ?? '')),
        ];

        if ($data['logo']) {
            $keys['logo_path'] = $data['logo']->store('branches/'.$branchId.'/branding', 'public');
        }

        DB::transaction(function () use ($keys, $branchId, $user): void {
            $before = BranchSetting::query()->where('branch_id', $branchId)->pluck('value', 'key')->all();

            foreach ($keys as $key => $value) {
                $group = in_array($key, ['delivery_paused', 'delivery_pause_reason'], true)
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

    public function render()
    {
        $user = auth()->user();

        return view('livewire.admin.settings.index', [
            'branches' => $user->hasRole('system_admin') ? Branch::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'isSystemAdmin' => $user->hasRole('system_admin'),
        ])->layout('layouts.admin', ['heading' => 'إعدادات المطعم والهوية']);
    }
}
