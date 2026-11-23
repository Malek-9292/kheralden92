<?php
namespace Database\\Seeders;
use App\\Modules\\Platform\\Services\\ModuleManager;
use Illuminate\\Database\\Seeder;
class SystemModuleSeeder extends Seeder {
 public function run(ModuleManager $modules): void {$modules->syncDefaults();}
}
