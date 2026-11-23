<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Printer extends Model
{
    protected $fillable = [
        'branch_id', 'code', 'name', 'purpose', 'connection_type',
        'device_token_hash', 'is_active', 'last_seen_at',
    ];

    protected $hidden = ['device_token_hash'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'last_seen_at' => 'immutable_datetime'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(PrintJob::class);
    }
}
