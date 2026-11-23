<?php

namespace App\Models\Concerns;

use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasRoles
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withPivot('assigned_by')->withTimestamps();
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles()
            ->where('slug', $slug)
            ->where(fn ($query) => $query->whereNull('roles.branch_id')->orWhere('roles.branch_id', $this->branch_id))
            ->exists();
    }

    public function hasPermissionTo(string $permission): bool
    {
        return $this->roles()
            ->where(fn ($query) => $query->whereNull('branch_id')->orWhere('branch_id', $this->branch_id))
            ->whereHas('permissions', fn ($query) => $query->where('slug', $permission))
            ->exists();
    }
}
