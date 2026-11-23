<?php

namespace Tests\Feature;

use App\Models\EmployeeSession;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResponsiveUiMarkupTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_includes_mobile_first_responsive_markers(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('viewport-fit=cover', $html);
        $this->assertStringContainsString('overflow-x:clip', $html);
        $this->assertStringContainsString('object-fit:contain', $html);
        $this->assertStringContainsString('min-width:360px', $html);
        $this->assertStringContainsString('min-width:768px', $html);
        $this->assertStringContainsString('min-width:1024px', $html);
        $this->assertStringContainsString('safe-area-inset-bottom', $html);
        $this->assertStringContainsString('modal-open', $html);
        $this->assertStringContainsString('class="media"', $html);
        $this->assertStringContainsString('--tap:44px', $html);
    }

    public function test_admin_layout_includes_rtl_drawer_and_table_card_markers(): void
    {
        $this->seed(DatabaseSeeder::class);

        $adminRole = Role::query()->where('slug', 'system_admin')->whereNull('branch_id')->firstOrFail();
        $user = User::query()->create([
            'name' => 'Responsive Admin',
            'phone' => '96551112233',
            'password' => 'PreviewPass123!',
            'status' => 'active',
            'branch_id' => null,
        ]);
        $user->roles()->attach($adminRole->id);

        $token = Str::random(64);
        EmployeeSession::query()->create([
            'user_id' => $user->id,
            'branch_id' => null,
            'session_hash' => hash('sha256', $token),
            'logged_in_at' => now(),
            'last_activity_at' => now(),
        ]);

        $html = $this->actingAs($user)
            ->withSession(['employee_session_token' => $token])
            ->get('/admin/dashboard')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('viewport-fit=cover', $html);
        $this->assertStringContainsString('translateX(110%)', $html);
        $this->assertStringContainsString('right:0', $html);
        $this->assertStringContainsString('max-width:720px', $html);
        $this->assertStringContainsString('content:attr(data-label)', $html);
        $this->assertStringContainsString('menu-toggle', $html);
        $this->assertStringContainsString('--tap:44px', $html);
        $this->assertStringContainsString('overflow-x:clip', $html);
    }

    public function test_admin_login_is_viewport_safe_for_mobile(): void
    {
        $html = $this->get('/admin/login')->assertOk()->getContent();

        $this->assertStringContainsString('viewport-fit=cover', $html);
        $this->assertStringContainsString('min-height:44px', $html);
        $this->assertStringContainsString('safe-area-inset', $html);
    }
}
