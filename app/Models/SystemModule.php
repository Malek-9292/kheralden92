<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemModule extends Model
{
    protected $fillable = ['code', 'name', 'is_enabled', 'is_core', 'dependencies'];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'is_core' => 'boolean',
            'dependencies' => 'array',
        ];
    }
}
