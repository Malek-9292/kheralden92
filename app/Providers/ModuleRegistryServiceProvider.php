<?php
namespace App\Providers;
use App\Support\Modules\ModuleManager;
use Illuminate\Support\ServiceProvider;
class ModuleRegistryServiceProvider extends ServiceProvider {
 public function register(): void {
  $manager = new ModuleManager(config('modules', []));
  $manager->registerEnabledProviders($this->app);
  $this->app->instance(ModuleManager::class, $manager);
 }
}