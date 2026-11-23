<?php

namespace Tests\Unit;

use App\Models\ModuleSetting;
use App\Support\Modules\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class ModuleManagerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function core_modules_cannot_be_disabled(): void
    {
        $manager = new ModuleManager([
            'Identity' => ['enabled' => true, 'core' => true, 'depends_on' => []],
        ]);

        $this->expectException(RuntimeException::class);
        $manager->assertCanDisable('Identity');
    }

    #[Test]
    public function a_module_cannot_be_disabled_while_an_enabled_module_depends_on_it(): void
    {
        $manager = new ModuleManager([
            'Catalog' => ['enabled' => true, 'core' => false, 'depends_on' => []],
            'Storefront' => ['enabled' => true, 'core' => false, 'depends_on' => ['Catalog']],
        ]);

        $this->expectException(RuntimeException::class);
        $manager->assertCanDisable('Catalog');
    }

    #[Test]
    public function database_overrides_control_module_enabled_state(): void
    {
        ModuleSetting::query()->create([
            'module_key' => 'Promotions',
            'is_enabled' => false,
            'settings' => null,
        ]);

        $manager = new ModuleManager([
            'Promotions' => ['enabled' => true, 'core' => false, 'depends_on' => []],
        ]);

        $this->assertFalse($manager->enabled('Promotions'));
    }

    #[Test]
    public function enabled_modules_must_have_enabled_dependencies(): void
    {
        ModuleSetting::query()->create([
            'module_key' => 'Catalog',
            'is_enabled' => false,
            'settings' => null,
        ]);

        $manager = new ModuleManager([
            'Catalog' => ['enabled' => true, 'core' => false, 'depends_on' => []],
            'Storefront' => ['enabled' => true, 'core' => false, 'depends_on' => ['Catalog']],
        ]);

        $this->expectException(RuntimeException::class);
        $manager->assertDependenciesSatisfied();
    }
}
