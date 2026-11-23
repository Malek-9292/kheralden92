<?php
namespace Tests\Unit;
use App\Modules\Platform\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;
class ModuleManagerTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        app(ModuleManager::class)->syncDefaults();
    }
    public function test_platform_module_is_enabled_by_default(): void
    {
        $this->assertTrue(app(ModuleManager::class)->isEnabled('platform'));
    }
    public function test_unknown_module_is_not_enabled(): void
    {
        $this->assertFalse(app(ModuleManager::class)->isEnabled('not-a-real-module'));
    }
    public function test_core_module_cannot_be_disabled(): void
    {
        $this->expectException(LogicException::class);
        app(ModuleManager::class)->disable('platform');
    }
    public function test_module_cannot_be_disabled_while_enabled_modules_depend_on_it(): void
    {
        $this->expectException(LogicException::class);
        app(ModuleManager::class)->disable('catalog');
    }
    public function test_independent_module_can_be_disabled_and_reenabled(): void
    {
        $modules = app(ModuleManager::class);
        $modules->disable('printing');
        $this->assertFalse($modules->isEnabled('printing'));
        $modules->enable('printing');
        $this->assertTrue($modules->isEnabled('printing'));
    }
}
