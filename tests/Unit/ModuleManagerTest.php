<?php
namespace Tests\\Unit;
use App\\Modules\\Platform\\Services\\ModuleManager;
use Tests\\TestCase;
class ModuleManagerTest extends TestCase {
 public function test_platform_module_is_enabled_by_default(): void {$this->assertTrue(app(ModuleManager::class)->isEnabled('platform'));}
 public function test_unknown_module_is_not_enabled(): void {$this->assertFalse(app(ModuleManager::class)->isEnabled('not-a-real-module'));}
}
