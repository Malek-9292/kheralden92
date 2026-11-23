<?php

namespace App\Models;

use App\Models\Concerns\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employeeSessions(): HasMany
    {
        return $this->hasMany(EmployeeSession::class);
    }

    protected $fillable = ['name', 'phone', 'email', 'password', 'branch_id', 'status', 'valid_from', 'valid_until'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'last_login_at' => 'datetime', 'valid_from' => 'datetime', 'valid_until' => 'datetime', 'password' => 'hashed'];
    }
}
