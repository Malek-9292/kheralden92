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

        return response()->json([
            'branch' => ['code' => $branch->code, 'name' => $branch->name],
            'settings' => [
                'restaurant_name' => $settings->get('restaurant_name', $branch->name),
                'tagline' => $settings->get('tagline', 'طعم يجمعنا'),
                'phone' => $settings->get('phone', ''),
                'whatsapp' => $settings->get('whatsapp', ''),
                'primary_color' => $settings->get('primary_color', '#2f704b'),
                'accent_color' => $settings->get('accent_color', '#8fcf9b'),
                'banner_text' => $settings->get('banner_text', ''),
                'banner_text_color' => $settings->get('banner_text_color', '#ffffff'),
                'banner_background' => $settings->get('banner_background', '#2f704b'),
                'banner_font_size' => (int) $settings->get('banner_font_size', 18),
                'footer_text' => $settings->get('footer_text', ''),
                'logo_url' => $logoPath ? Storage::disk('public')->url($logoPath) : null,
            ],
        ]);
    }
}
