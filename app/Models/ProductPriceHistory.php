<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceHistory extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_id', 'branch_id', 'changed_by', 'old_price', 'new_price', 'reason', 'created_at'];

    protected function casts(): array
    {
        return [
            'old_price' => 'decimal:3',
            'new_price' => 'decimal:3',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
