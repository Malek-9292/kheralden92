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

        $placeholder = public_path('images/product-placeholder.jpg');
        $this->assertFileExists($placeholder);
        $size = getimagesize($placeholder);
        $this->assertNotFalse($size);
        $this->assertGreaterThanOrEqual(640, $size[0]);
        $this->assertGreaterThanOrEqual(480, $size[1]);
        $this->assertFileExists(public_path('images/placeholders/sandwiches.jpg'));
        $this->assertFileExists(public_path('images/placeholders/saj.jpg'));
        $this->assertFileExists(public_path('images/placeholders/grill.jpg'));
        $this->assertFileExists(public_path('images/menu/شاورما لحم.jpg'));
        $this->assertFileExists(public_path('images/menu/شاورما لحم صاج.jpg'));
        $this->assertFileExists(public_path('images/menu/فتوش.jpg'));

        $shawarma = Product::query()->where('name', 'شاورما لحم')->with('images')->first();
        $this->assertNotNull($shawarma);
        $this->assertTrue($shawarma->images->isNotEmpty());
        $this->assertSame('شاورما لحم.jpg', $shawarma->images->first()->original_name);
        $this->assertFileExists(storage_path('app/public/'.$shawarma->images->first()->path));

        // Prefer the more specific صاج filename when both stems match.
        $saj = Product::query()->where('name', 'شاورما لحم صاج')->with('images')->first();
        $this->assertNotNull($saj);
        $this->assertSame('شاورما لحم صاج.jpg', $saj->images->first()->original_name);

        $fatoosh = Product::query()->where('name', 'فتوش')->with('images')->first();
        $this->assertNotNull($fatoosh);
        $this->assertSame('فتوش.jpg', $fatoosh->images->first()?->original_name);
    }
}
