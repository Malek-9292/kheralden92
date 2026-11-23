<?php

namespace Tests\Feature\Storefront;

use App\Models\Branch;
use App\Models\BranchSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_storefront_settings_return_branch_branding(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        BranchSetting::query()->create([
            'branch_id' => $branch->id,
            'setting_group' => 'branding',
            'key' => 'restaurant_name',
            'value' => 'فتوش الخالدية المميز',
        ]);
        BranchSetting::query()->create([
            'branch_id' => $branch->id,
            'setting_group' => 'branding',
            'key' => 'primary_color',
            'value' => '#336644',
        ]);

        $this->getJson('/api/storefront/KHAL/settings')
            ->assertOk()
            ->assertJsonPath('settings.restaurant_name', 'فتوش الخالدية المميز')
            ->assertJsonPath('settings.primary_color', '#336644')
            ->assertJsonPath('settings.tagline', 'طعم يجمعنا')
            ->assertJsonPath('settings.show_storefront_top_block', false)
            ->assertJsonPath('settings.brand_box_background', '#ffffff')
            ->assertJsonPath('settings.brand_name_color', '#1d5b3d');

        BranchSetting::query()->create([
            'branch_id' => $branch->id,
            'setting_group' => 'operations',
            'key' => 'show_storefront_top_block',
            'value' => '1',
        ]);
        BranchSetting::query()->create([
            'branch_id' => $branch->id,
            'setting_group' => 'branding',
            'key' => 'brand_name_color',
            'value' => '#112233',
        ]);
        BranchSetting::query()->create([
            'branch_id' => $branch->id,
            'setting_group' => 'branding',
            'key' => 'brand_box_background',
            'value' => '#fafafa',
        ]);

        $this->getJson('/api/storefront/KHAL/settings')
            ->assertOk()
            ->assertJsonPath('settings.show_storefront_top_block', true)
            ->assertJsonPath('settings.brand_name_color', '#112233')
            ->assertJsonPath('settings.brand_box_background', '#fafafa');

        $branch->update(['is_active' => false]);

        $this->getJson('/api/storefront/KHAL/settings')->assertNotFound();
    }
}
