<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\EmployeeSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminLoginPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_arabic_login_page(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('تسجيل دخول الموظفين')
            ->assertSee('فتوش الخالدية')
            ->assertSee('لا توجد كلمة مرور افتراضية', false)
            ->assertSee('IBM+Plex+Sans+Arabic')
            ->assertSee('redirect_to');
    }

    public function test_authenticated_employee_is_redirected_away_from_login(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Employee',
            'phone' => '96563330000',
            'password' => 'employee-password-12',
            'status' => 'active',
        ]);

        $token = Str::random(64);
        EmployeeSession::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'session_hash' => hash('sha256', $token),
            'logged_in_at' => now(),
            'last_activity_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['employee_session_token' => $token])
            ->get('/admin/login')
            ->assertRedirect('/admin/dashboard');
    }
}
