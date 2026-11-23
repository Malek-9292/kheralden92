<?php
namespace App\Support\Modules;
use Illuminate\Contracts\Foundation\Application;
use RuntimeException;
final class ModuleManager {
 public function __construct(private readonly array $modules) {}
 public function enabled(string $module): bool { return (bool) ($this->modules[$module]['enabled'] ?? false); }
 public function enabledModules(): array { return array_keys(array_filter($this->modules, fn (array $d) => (bool) ($d['enabled'] ?? false))); }
 public function registerEnabledProviders(Application $app): void {
  foreach ($this->modules as $name => $definition) {
   if (!($definition['enabled'] ?? false)) continue;
   $provider = $definition['provider'] ?? null;
   if (!is_string($provider) || !class_exists($provider)) throw new RuntimeException("Enabled module [{$name}] has no valid service provider.");
   $app->register($provider);
  }
 }
}