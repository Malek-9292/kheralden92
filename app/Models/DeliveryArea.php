<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryArea extends Model
{
    protected $fillable = [
        'branch_id', 'governorate', 'name', 'delivery_fee',
        'minimum_order', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'delivery_fee' => 'decimal:3',
            'minimum_order' => 'decimal:3',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
