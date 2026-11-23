<?php

namespace Tests\Feature;

use App\Models\ModuleSetting;
use App\Support\Modules\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ModuleManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_enabled_modules_are_listed(): void
    {
        $manager = new ModuleManager([
            'Orders' => ['enabled' => true, 'depends_on' => []],
            'Inventory' => ['enabled' => false, 'depends_on' => []],
        ]);

        $this->assertSame(['Orders'], $manager->enabledModules());
        $this->assertTrue($manager->enabled('Orders'));
        $this->assertFalse($manager->enabled('Inventory'));
    }

    public function test_core_modules_cannot_be_disabled(): void
    {
        $manager = new ModuleManager([
            'Identity' => ['enabled' => true, 'core' => true, 'depends_on' => []],
        ]);

        $this->expectException(RuntimeException::class);
        $manager->assertCanDisable('Identity');
    }

    public function test_disabling_breaks_when_another_enabled_module_depends_on_it(): void
    {
        $manager = new ModuleManager([
            'Catalog' => ['enabled' => true, 'core' => false, 'depends_on' => []],
            'Cart' => ['enabled' => true, 'core' => false, 'depends_on' => ['Catalog']],
        ]);

        $this->expectException(RuntimeException::class);
        $manager->assertCanDisable('Catalog');
    }

    public function test_database_module_settings_override_config_enabled_flag(): void
    {
        ModuleSetting::query()->create([
            'module_key' => 'Promotions',
            'is_enabled' => false,
            'settings' => null,
        ]);

        $manager = new ModuleManager([
            'Promotions' => ['enabled' => true, 'core' => false, 'depends_on' => []],
            'Identity' => ['enabled' => true, 'core' => true, 'depends_on' => []],
        ]);

        $this->assertFalse($manager->enabled('Promotions'));
        $this->assertTrue($manager->enabled('Identity'));
    }

    public function test_unsatisfied_dependencies_are_rejected(): void
    {
        $manager = new ModuleManager([
            'Cart' => ['enabled' => true, 'depends_on' => ['Catalog']],
            'Catalog' => ['enabled' => false, 'depends_on' => []],
        ]);

        $this->expectException(RuntimeException::class);
        $manager->assertDependenciesSatisfied();
    }
}
