<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'branch_id', 'delivery_area_id', 'order_number', 'customer_name', 'customer_phone',
        'fulfillment_type', 'status', 'payment_method', 'payment_status',
        'delivery_governorate', 'delivery_area_name', 'block', 'street', 'avenue',
        'house_number', 'floor', 'apartment', 'customer_notes', 'subtotal',
        'delivery_fee', 'minimum_order_snapshot', 'total', 'tracking_token_hash',
        'confirmed_at', 'completed_at', 'cancelled_at',
    ];

    protected $hidden = ['tracking_token_hash'];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:3',
            'delivery_fee' => 'decimal:3',
            'minimum_order_snapshot' => 'decimal:3',
            'total' => 'decimal:3',
            'confirmed_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function deliveryArea(): BelongsTo
    {
        return $this->belongsTo(DeliveryArea::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
