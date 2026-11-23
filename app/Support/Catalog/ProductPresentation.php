<?php

namespace App\Support\Catalog;

use App\Models\Product;

class ProductPresentation
{
    public const BREAD_SAMOON = 'samoon';

    public const BREAD_SAJ = 'saj';

    public const BREAD_NONE = 'none';

    /**
     * Category slug defaults for sandwich bread. Exceptions must be stored on the product.
     */
    public static function categoryDefaultBread(?string $categorySlug): string
    {
        return match ($categorySlug) {
            'light-sandwiches', 'hot-cold-sandwiches' => self::BREAD_SAMOON,
            'saj-sandwiches' => self::BREAD_SAJ,
            default => self::BREAD_NONE,
        };
    }

    public static function resolveBreadType(Product $product): string
    {
        if (filled($product->bread_type)) {
            return (string) $product->bread_type;
        }

        return self::categoryDefaultBread($product->relationLoaded('category')
            ? $product->category?->slug
            : $product->category()->value('slug'));
    }

    public static function breadLabel(string $breadType): ?string
    {
        return match ($breadType) {
            self::BREAD_SAMOON => 'خبز صمون',
            self::BREAD_SAJ => 'خبز صاج',
            default => null,
        };
    }

    /**
     * Grill-tray style ingredients never appear in the main product list.
     */
    public static function toCustomerListArray(Product $product): array
    {
        $bread = self::resolveBreadType($product);

        return [
            'id' => $product->id,
            'branch_id' => $product->branch_id,
            'category_id' => $product->category_id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'price' => $product->price,
            'compare_at_price' => $product->compare_at_price,
            'is_active' => $product->is_active,
            'is_best_seller' => $product->is_best_seller,
            'sort_order' => $product->sort_order,
            'bread_type' => $bread,
            'bread_label' => self::breadLabel($bread),
            'has_detail_ingredients' => filled($product->ingredients),
            'category' => $product->relationLoaded('category') ? $product->category : null,
            'primary_image' => $product->relationLoaded('primaryImage') ? $product->primaryImage : null,
            'images' => $product->relationLoaded('images') ? $product->images : null,
            'availability_windows' => $product->relationLoaded('availabilityWindows') ? $product->availabilityWindows : null,
        ];
    }

    public static function toCustomerDetailArray(Product $product): array
    {
        $payload = self::toCustomerListArray($product);
        $payload['ingredients'] = $product->ingredients;

        return $payload;
    }
}
