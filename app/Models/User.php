<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Branch;
use App\Models\Concerns\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class User extends Authenticatable {
 use HasFactory, Notifiable, HasRoles;
 public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
 public function employeeSessions(): HasMany { return $this->hasMany(EmployeeSession::class); }
 protected $fillable = ['name','phone','email','password','branch_id','status'];
 protected $hidden = ['password','remember_token'];
 protected function casts(): array { return ['email_verified_at'=>'datetime','password'=>'hashed']; }
}