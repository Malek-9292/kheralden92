<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\EmployeeSession;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_search_section_links_to_existing_admin_routes(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $permission = Permission::query()->create([
            'name' => 'View catalog',
            'slug' => 'catalog.view',
            'module' => 'Catalog',
        ]);

        $role = Role::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Content',
            'slug' => 'content_manager',
            'is_system' => true,
        ]);
        $role->permissions()->sync([$permission->id]);

        $user = User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Content Manager',
            'phone' => '96568880000',
            'password' => 'content-password-12',
            'status' => 'active',
        ]);
        $user->roles()->attach($role->id);

        $token = Str::random(64);
        EmployeeSession::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'session_hash' => hash('sha256', $token),
            'logged_in_at' => now(),
            'last_activity_at' => now(),
        ]);

        $this->actingAs($user)->withSession(['employee_session_token' => $token]);

        $response = $this->getJson('/admin/search?q=الأصناف')->assertOk();
        $urls = collect($response->json('results.sections') ?? [])->pluck('url')->all();

        $this->assertContains('/admin/products', $urls);
        $this->assertNotContains('/admin/catalog', $urls);

        $categories = $this->getJson('/admin/search?q=الأقسام')->assertOk();
        $categoryUrls = collect($categories->json('results.sections') ?? [])->pluck('url')->all();
        $this->assertContains('/admin/categories', $categoryUrls);
    }
}
