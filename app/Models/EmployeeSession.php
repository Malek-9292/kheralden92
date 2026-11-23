<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSession extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'branch_id', 'session_hash', 'ip_address', 'user_agent',
        'logged_in_at', 'last_activity_at', 'logged_out_at', 'ended_reason',
    ];

    protected function casts(): array
    {
        return [
            'logged_in_at' => 'immutable_datetime',
            'last_activity_at' => 'immutable_datetime',
            'logged_out_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
