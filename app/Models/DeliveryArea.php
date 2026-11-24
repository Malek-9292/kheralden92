<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class DeliveryArea extends Model
{
    protected $fillable = [
        'branch_id', 'delivery_area_group_id', 'governorate', 'name', 'delivery_fee',
        'minimum_order', 'delivery_fee_override', 'minimum_order_override',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'delivery_fee' => 'decimal:3',
            'minimum_order' => 'decimal:3',
            'delivery_fee_override' => 'decimal:3',
            'minimum_order_override' => 'decimal:3',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(DeliveryAreaGroup::class, 'delivery_area_group_id');
    }

    public function effectiveDeliveryFee(): string
    {
        return (string) ($this->delivery_fee_override ?? $this->group?->delivery_fee ?? $this->delivery_fee);
    }

    public function effectiveMinimumOrder(): string
    {
        return (string) ($this->minimum_order_override ?? $this->group?->minimum_order ?? $this->minimum_order);
    }
}
