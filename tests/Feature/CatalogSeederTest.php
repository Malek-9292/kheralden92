<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_loads_approved_menu_products_with_images(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(100, Product::query()->count());
        $this->assertDatabaseHas('products', [
            'name' => 'صينية مشويات 5 أشخاص',
        ]);
        $this->assertTrue(
            Product::query()->where('name', 'صينية مشويات 5 أشخاص')->whereNotNull('ingredients')->exists(),
        );
        $this->assertTrue(
            Product::query()->where('name', 'شاورما دجاج')->whereHas('images', fn ($q) => $q->where('is_primary', true))->exists(),
        );
    }
}
