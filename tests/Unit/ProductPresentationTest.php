<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Support\Catalog\ProductPresentation;
use PHPUnit\Framework\TestCase;

class ProductPresentationTest extends TestCase
{
    public function test_category_defaults_map_sandwich_slugs_to_bread(): void
    {
        $this->assertSame('samoon', ProductPresentation::categoryDefaultBread('light-sandwiches'));
        $this->assertSame('samoon', ProductPresentation::categoryDefaultBread('hot-cold-sandwiches'));
        $this->assertSame('saj', ProductPresentation::categoryDefaultBread('saj-sandwiches'));
        $this->assertSame('none', ProductPresentation::categoryDefaultBread('grill-trays'));
    }

    public function test_resolve_bread_type_prefers_explicit_product_value(): void
    {
        $category = new Category(['slug' => 'light-sandwiches']);
        $product = new Product(['bread_type' => 'saj']);
        $product->setRelation('category', $category);

        $this->assertSame('saj', ProductPresentation::resolveBreadType($product));
        $this->assertSame('خبز صاج', ProductPresentation::breadLabel('saj'));
        $this->assertSame('خبز صمون', ProductPresentation::breadLabel('samoon'));
        $this->assertNull(ProductPresentation::breadLabel('none'));
    }
}
