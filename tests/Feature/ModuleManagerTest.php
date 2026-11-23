<?php
namespace Tests\Feature;
use App\Support\Modules\ModuleManager;
use PHPUnit\Framework\TestCase;
class ModuleManagerTest extends TestCase {
 public function test_only_enabled_modules_are_listed(): void {
  $manager = new ModuleManager(['Orders'=>['enabled'=>true],'Inventory'=>['enabled'=>false]]);
  self::assertSame(['Orders'],$manager->enabledModules()); self::assertTrue($manager->enabled('Orders')); self::assertFalse($manager->enabled('Inventory'));
 }
}