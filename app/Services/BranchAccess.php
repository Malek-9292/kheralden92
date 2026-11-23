<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

final class BranchAccess
{
    public function visibleBranchIds(User $user): Collection
    {
        if ($user->hasRole('system_admin')) {
            return Branch::query()->pluck('id');
        }

        return $user->branch_id === null
            ? collect()
            : collect([(int) $user->branch_id]);
    }

    public function assertCanAccess(User $user, Branch|int $branch): void
    {
        $branchId = $branch instanceof Branch ? $branch->getKey() : $branch;

        if (! $this->visibleBranchIds($user)->contains((int) $branchId)) {
            throw new AuthorizationException('لا تملك صلاحية الوصول إلى هذا الفرع.');
        }
    }
}
