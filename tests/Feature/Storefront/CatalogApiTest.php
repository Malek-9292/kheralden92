<?php

namespace Tests\Feature\Storefront;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAvailabilityWindow;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        parent::tearDown();
        Carbon::setTestNow();
    }

    public function test_public_catalog_only_returns_active_categories_for_an_active_branch(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        Category::query()->create([
            'branch_id' => $branch->id,
            'name' => 'العصائر',
            'slug' => 'juices',
            'is_active' => true,
        ]);

        Category::query()->create([
            'branch_id' => $branch->id,
            'name' => 'قسم مخفي',
            'slug' => 'hidden',
            'is_active' => false,
        ]);

        $this->getJson('/api/catalog/khal/categories')
            ->assertOk()
            ->assertJsonCount(1, 'categories')
            ->assertJsonPath('categories.0.slug', 'juices');

        $branch->update(['is_active' => false]);

        $this->getJson('/api/catalog/KHAL/categories')->assertNotFound();
    }

    public function test_public_products_respect_category_status_and_availability_windows(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-11-23 12:00:00', 'Asia/Kuwait'),
        );

        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $activeCategory = Category::query()->create([
            'branch_id' => $branch->id,
            'name' => 'العصائر',
            'slug' => 'juices',
            'is_active' => true,
        ]);

        $inactiveCategory = Category::query()->create([
            'branch_id' => $branch->id,
            'name' => 'قسم مخفي',
            'slug' => 'hidden',
            'is_active' => false,
        ]);

        $available = Product::query()->create([
            'branch_id' => $branch->id,
            'category_id' => $activeCategory->id,
            'name' => 'عصير برتقال',
            'slug' => 'orange-juice',
            'price' => '1.500',
            'is_active' => true,
        ]);

        ProductAvailabilityWindow::query()->create([
            'product_id' => $available->id,
            'start_time' => '11:00:00',
            'end_time' => '15:00:00',
            'is_active' => true,
        ]);

        $unavailable = Product::query()->create([
            'branch_id' => $branch->id,
            'category_id' => $activeCategory->id,
            'name' => 'عصير غير متاح',
            'slug' => 'unavailable-juice',
            'price' => '1.000',
            'is_active' => true,
        ]);

        ProductAvailabilityWindow::query()->create([
            'product_id' => $unavailable->id,
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'is_active' => true,
        ]);

        Product::query()->create([
            'branch_id' => $branch->id,
            'category_id' => $inactiveCategory->id,
            'name' => 'صنف قسم مخفي',
            'slug' => 'hidden-category-product',
            'price' => '1.000',
            'is_active' => true,
        ]);

        $this->getJson('/api/catalog/KHAL/products')
            ->assertOk()
            ->assertJsonCount(1, 'products.data')
            ->assertJsonPath('products.data.0.slug', 'orange-juice');

        $this->getJson('/api/catalog/KHAL/products/orange-juice')
            ->assertOk()
            ->assertJsonPath('product.slug', 'orange-juice');

        $this->getJson('/api/catalog/KHAL/products/unavailable-juice')->assertNotFound();
        $this->getJson('/api/catalog/KHAL/products/hidden-category-product')->assertNotFound();
    }

    public function test_public_products_allow_large_page_size_for_full_menu(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'branch_id' => $branch->id,
            'name' => 'سندويشات',
            'slug' => 'sandwiches',
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 65; $i++) {
            Product::query()->create([
                'branch_id' => $branch->id,
                'category_id' => $category->id,
                'name' => "صنف {$i}",
                'slug' => "item-{$i}",
                'price' => '1.000',
                'is_active' => true,
                'sort_order' => $i,
            ]);
        }

        $this->getJson('/api/catalog/KHAL/products?per_page=100')
            ->assertOk()
            ->assertJsonPath('products.per_page', 100)
            ->assertJsonCount(65, 'products.data');
    }
}
