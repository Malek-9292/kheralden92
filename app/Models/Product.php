<?php

namespace App\Models;

use App\Support\Catalog\ProductPresentation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id', 'category_id', 'name', 'slug', 'description', 'bread_type', 'ingredients',
        'price', 'compare_at_price', 'is_active', 'is_best_seller', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:3',
            'compare_at_price' => 'decimal:3',
            'is_active' => 'boolean',
            'is_best_seller' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function availabilityWindows(): HasMany
    {
        return $this->hasMany(ProductAvailabilityWindow::class)->orderBy('sort_order');
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class)->orderByDesc('created_at');
    }

    public function resolvedBreadType(): string
    {
        return ProductPresentation::resolveBreadType($this);
    }

    public function breadLabel(): ?string
    {
        return ProductPresentation::breadLabel($this->resolvedBreadType());
    }
}
