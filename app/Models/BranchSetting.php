<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchSetting extends Model
{
    protected $fillable = ['branch_id', 'setting_group', 'key', 'value'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
