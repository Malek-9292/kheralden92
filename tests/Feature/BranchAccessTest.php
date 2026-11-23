<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Services\BranchAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_access_only_their_assigned_branch(): void
    {
        $first = Branch::create(['code' => 'A', 'name' => 'Branch A', 'timezone' => 'Asia/Kuwait']);
        $second = Branch::create(['code' => 'B', 'name' => 'Branch B', 'timezone' => 'Asia/Kuwait']);
        $employee = User::create([
            'branch_id' => $first->id,
            'name' => 'Branch Employee',
            'phone' => '96511111111',
            'password' => 'secret',
            'status' => 'active',
        ]);

        $access = app(BranchAccess::class);
        $access->assertCanAccess($employee, $first);

        $this->expectException(AuthorizationException::class);
        $access->assertCanAccess($employee, $second);
    }
}
