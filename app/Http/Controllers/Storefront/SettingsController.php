<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function show(string $branchCode): JsonResponse
    {
        $branch = Branch::query()
            ->where('code', strtoupper($branchCode))
            ->where('is_active', true)
            ->firstOrFail();

        $settings = BranchSetting::query()
            ->where('branch_id', $branch->id)
            ->pluck('value', 'key');

        $logoPath = $settings->get('logo_path');

        $deliveryPaused = filter_var($settings->get('delivery_paused', '0'), FILTER_VALIDATE_BOOLEAN);
        $deliveryPauseReason = (string) $settings->get('delivery_pause_reason', '');
        $showTopBlock = filter_var($settings->get('show_storefront_top_block', '0'), FILTER_VALIDATE_BOOLEAN);

        return response()->json([
            'branch' => ['code' => $branch->code, 'name' => $branch->name],
            'settings' => [
                'restaurant_name' => $settings->get('restaurant_name', $branch->name),
                'tagline' => $settings->get('tagline', 'طعم يجمعنا'),
                'phone' => $settings->get('phone', ''),
                'whatsapp' => $settings->get('whatsapp', ''),
                'primary_color' => $settings->get('primary_color', '#2f704b'),
                'accent_color' => $settings->get('accent_color', '#8fcf9b'),
                'brand_box_background' => $settings->get('brand_box_background', '#ffffff'),
                'brand_box_border_color' => $settings->get('brand_box_border_color', '#e7eee8'),
                'brand_name_color' => $settings->get('brand_name_color', '#1d5b3d'),
                'brand_tagline_color' => $settings->get('brand_tagline_color', '#6f8376'),
                'brand_logo_background' => $settings->get('brand_logo_background', '#f3f7f3'),
                'banner_text' => $settings->get('banner_text', ''),
                'banner_text_color' => $settings->get('banner_text_color', '#ffffff'),
                'banner_background' => $settings->get('banner_background', '#2f704b'),
                'banner_font_size' => (int) $settings->get('banner_font_size', 18),
                'footer_text' => $settings->get('footer_text', ''),
                'featured_products_by_category' => json_decode((string) $settings->get('storefront_featured_products', '{}'), true) ?: [],
                'logo_url' => $logoPath ? Storage::disk('public')->url($logoPath) : null,
                'show_storefront_top_block' => $showTopBlock,
                'delivery_paused' => $deliveryPaused,
                'delivery_pause_reason' => $deliveryPauseReason,
                'is_accepting_delivery' => ! $deliveryPaused,
            ],
        ]);
    }
}
