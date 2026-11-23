<?php

namespace Tests\Feature;

use App\Livewire\Admin\Modules\Index as ModulesIndex;
use App\Models\Branch;
use App\Models\ModuleSetting;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModuleManagerScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_admin_can_disable_optional_module(): void
    {
        $user = $this->createSystemAdmin();

        $this->actingAs($user);

        Livewire::test(ModulesIndex::class)
            ->call('toggle', 'Promotions')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('module_settings', [
            'module_key' => 'Promotions',
            'is_enabled' => false,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'module.disabled',
        ]);
    }

    public function test_module_manager_prevents_disabling_required_module(): void
    {
        $user = $this->createSystemAdmin();

        $this->actingAs($user);

        Livewire::test(ModulesIndex::class)
            ->call('toggle', 'Catalog')
            ->assertSee('يجب تفعيل الوحدات المطلوبة أولًا');

        $this->assertDatabaseMissing('module_settings', [
            'module_key' => 'Catalog',
            'is_enabled' => false,
        ]);
    }

    private function createSystemAdmin(): User
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $permission = Permission::query()->create([
            'name' => 'Manage modules',
            'slug' => 'modules.manage',
            'module' => 'System',
        ]);

        $role = Role::query()->create([
            'branch_id' => null,
            'name' => 'System Admin',
            'slug' => 'system_admin',
            'is_system' => true,
        ]);
        $role->permissions()->attach($permission->id);

        $user = User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'System Admin',
            'phone' => '96568888888',
            'password' => 'system-admin-password-123',
            'status' => 'active',
        ]);
        $user->roles()->attach($role->id);

        return $user;
    }
}
