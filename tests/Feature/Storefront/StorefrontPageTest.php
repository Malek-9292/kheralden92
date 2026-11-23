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
            ->assertSee('/api/catalog/');
    }
}
