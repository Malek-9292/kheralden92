<?php
use Illuminate\Support\Facades\Artisan;
Artisan::command('modules:list', function (): void {
 foreach (app(App\Support\Modules\ModuleManager::class)->enabledModules() as $module) $this->line($module);
})->purpose('List enabled application modules');