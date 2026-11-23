<?php

namespace Tests\Feature\Storefront;

use Tests\TestCase;

class StorefrontPageTest extends TestCase
{
    public function test_customer_homepage_renders_the_storefront_and_pwa_manifest_link(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('فتوش الخالدية')
            ->assertSee('/manifest.webmanifest')
            ->assertSee('/api/catalog/')
            ->assertSee('/api/storefront/')
            ->assertSee('تتبع طلب')
            ->assertSee('الدفع نقدًا عند الاستلام')
            ->assertSee('إتمام الطلب')
            ->assertSee('aria-modal')
            ->assertSee('loadingSkeleton')
            ->assertSee('offlineBanner')
            ->assertSee('serviceWorker')
            ->assertSee('IBM+Plex+Sans+Arabic')
            ->assertSee('viewport-fit=cover', false)
            ->assertSee('object-fit:contain', false)
            ->assertSee('min-width:1024px', false)
            ->assertSee('sf-header', false)
            ->assertSee('sf-fulfillment', false)
            ->assertSee('استلام من المطعم')
            ->assertSee('التوصيل')
            ->assertSee('حسابي')
            ->assertSee('fulfillmentPickup', false)
            ->assertSee('flex-direction:column', false)
            ->assertSee('areaGateModal', false)
            ->assertSee('اختر منطقة التوصيل')
            ->assertSee('الحد الأدنى للتوصيل', false)
            ->assertSee('cartAreaHint', false)
            ->assertSee('needsDeliveryArea', false)
            ->assertSee('openAreaGate', false)
            ->assertSee('fatoosh-delivery', false)
            ->assertSee('gateGovernorate', false)
            ->assertSee('storefrontTopBlock', false)
            ->assertSee('show_storefront_top_block', false)
            ->assertSee('brand-box-bg', false)
            ->assertSee('brand_name_color', false)
            ->assertSee('cartbar.is-empty', false)
            ->assertSee("el('openCart').hidden=!count", false)
            ->assertSee('sf-brand-logo', false)
            ->assertSee('sf-brand-copy', false)
            ->assertSee('aspect-ratio:21/9', false)
            ->assertSee('product-actions', false)
            ->assertSee('aspect-ratio:1/1', false)
            ->assertSee('align-items:stretch', false);

        $this->assertFileExists(public_path('manifest.webmanifest'));
        $this->assertStringContainsString('فتوش الخالدية', file_get_contents(public_path('manifest.webmanifest')));
        $this->assertFileExists(public_path('sw.js'));
        $this->assertStringContainsString('fatoosh-storefront-v2', file_get_contents(public_path('sw.js')));
    }
}
