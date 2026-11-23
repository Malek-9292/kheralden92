<?php

namespace Tests\Feature;

use App\Models\ModuleSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleRouteMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabling_storefront_module_hides_customer_homepage(): void
    {
        ModuleSetting::query()->create([
            'module_key' => 'Storefront',
            'is_enabled' => false,
            'settings' => null,
        ]);

        $this->get('/')->assertNotFound();
    }

    public function test_disabling_catalog_module_hides_public_catalog_api(): void
    {
        ModuleSetting::query()->create([
            'module_key' => 'Catalog',
            'is_enabled' => false,
            'settings' => null,
        ]);

        $this->getJson('/api/catalog/KHAL/categories')->assertNotFound();
    }
}
