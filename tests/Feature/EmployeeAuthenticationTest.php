<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\EmployeeSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_employee_can_sign_in_and_session_is_recorded(): void
    {
        $branch = Branch::create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $employee = User::create([
            'branch_id' => $branch->id,
            'name' => 'Test Cashier',
            'phone' => '96555550000',
            'password' => 'correct-password',
            'status' => 'active',
        ]);

        $response = $this->postJson('/admin/session', [
            'phone' => '96555550000',
            'password' => 'correct-password',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.id', $employee->id)
            ->assertJsonPath('user.branch.id', $branch->id);

        $this->assertDatabaseHas('employee_sessions', [
            'user_id' => $employee->id,
            'branch_id' => $branch->id,
            'logged_out_at' => null,
        ]);
        $this->assertDatabaseHas('employee_login_attempts', [
            'user_id' => $employee->id,
            'was_successful' => true,
        ]);
    }

    public function test_suspended_employee_cannot_sign_in(): void
    {
        User::create([
            'name' => 'Suspended Employee',
            'phone' => '96555550001',
            'password' => 'correct-password',
            'status' => 'suspended',
        ]);

        $this->postJson('/admin/session', [
            'phone' => '96555550001',
            'password' => 'correct-password',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('employee_login_attempts', [
            'login_identifier' => '96555550001',
            'was_successful' => false,
        ]);
    }

    public function test_revoked_employee_session_cannot_access_admin_routes(): void
    {
        $employee = User::create([
            'name' => 'Revoked Session Employee',
            'phone' => '96555550003',
            'password' => 'correct-password',
            'status' => 'active',
        ]);

        $this->postJson('/admin/session', [
            'phone' => '96555550003',
            'password' => 'correct-password',
        ])->assertOk();

        $employee->employeeSessions()->update([
            'logged_out_at' => now(),
            'ended_reason' => 'admin_revoked',
        ]);

        $this->getJson('/admin/session')->assertUnauthorized();
    }

    public function test_employee_can_sign_out_and_session_is_closed(): void
    {
        $employee = User::create([
            'name' => 'Test Employee',
            'phone' => '96555550002',
            'password' => 'correct-password',
            'status' => 'active',
        ]);

        $this->postJson('/admin/session', [
            'phone' => '96555550002',
            'password' => 'correct-password',
        ])->assertOk();

        $session = EmployeeSession::query()->where('user_id', $employee->id)->firstOrFail();

        $this->deleteJson('/admin/session')->assertOk();

        $this->assertNotNull($session->fresh()->logged_out_at);
        $this->assertGuest();
    }
}
