<?php

namespace Tests\Feature\Storefront;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogBreadAndIngredientsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sandwich_categories_default_to_samoon_and_saj_bread_labels(): void
    {
        [$branch, $light, $saj] = $this->seedBranchWithSandwichCategories();

        Product::query()->create([
            'branch_id' => $branch->id,
            'category_id' => $light->id,
            'name' => 'شاورما دجاج',
            'slug' => 'shawarma-chicken',
            'price' => '0.650',
            'is_active' => true,
        ]);

        Product::query()->create([
            'branch_id' => $branch->id,
            'category_id' => $saj->id,
            'name' => 'شاورما دجاج صاج',
            'slug' => 'shawarma-chicken-saj',
            'price' => '0.850',
            'is_active' => true,
        ]);

        $this->getJson('/api/catalog/KHAL/products')
            ->assertOk()
            ->assertJsonPath('products.data.0.bread_label', 'خبز صمون')
            ->assertJsonPath('products.data.1.bread_label', 'خبز صاج')
            ->assertJsonMissingPath('products.data.0.ingredients');
    }

    public function test_grill_tray_ingredients_appear_only_on_product_detail(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'branch_id' => $branch->id,
            'name' => 'صواني مشويات',
            'slug' => 'grill-trays',
            'is_active' => true,
        ]);

        Product::query()->create([
            'branch_id' => $branch->id,
            'category_id' => $category->id,
            'name' => 'صينية مشويات 5 أشخاص',
            'slug' => 'grill-tray-5',
            'description' => 'صينية مشويات عائلية',
            'ingredients' => "كباب\nطاووق\nكفتة",
            'price' => '17.500',
            'is_active' => true,
        ]);

        $list = $this->getJson('/api/catalog/KHAL/products')->assertOk();
        $list->assertJsonPath('products.data.0.slug', 'grill-tray-5');
        $list->assertJsonPath('products.data.0.has_detail_ingredients', true);
        $this->assertArrayNotHasKey('ingredients', $list->json('products.data.0'));

        $this->getJson('/api/catalog/KHAL/products/grill-tray-5')
            ->assertOk()
            ->assertJsonPath('product.ingredients', "كباب\nطاووق\nكفتة")
            ->assertJsonPath('product.bread_label', null);
    }

    public function test_explicit_bread_type_overrides_category_default(): void
    {
        [$branch, $light] = $this->seedBranchWithSandwichCategories();

        Product::query()->create([
            'branch_id' => $branch->id,
            'category_id' => $light->id,
            'name' => 'ساندويتش خاص',
            'slug' => 'special-sandwich',
            'bread_type' => 'none',
            'price' => '0.500',
            'is_active' => true,
        ]);

        $this->getJson('/api/catalog/KHAL/products/special-sandwich')
            ->assertOk()
            ->assertJsonPath('product.bread_type', 'none')
            ->assertJsonPath('product.bread_label', null);
    }

    /**
     * @return array{0: Branch, 1: Category, 2?: Category}
     */
    private function seedBranchWithSandwichCategories(): array
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $light = Category::query()->create([
            'branch_id' => $branch->id,
            'name' => 'الساندويتشات الخفيفة',
            'slug' => 'light-sandwiches',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $saj = Category::query()->create([
            'branch_id' => $branch->id,
            'name' => 'ساندويتشات الصاج',
            'slug' => 'saj-sandwiches',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return [$branch, $light, $saj];
    }
}
