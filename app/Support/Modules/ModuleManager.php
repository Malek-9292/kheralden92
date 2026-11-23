<?php

namespace App\Support\Modules;

use App\Models\ModuleSetting;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class ModuleManager
{
    /** @param array<string, array{enabled?: bool, provider?: class-string, depends_on?: list<string>, core?: bool}> $modules */
    public function __construct(private array $modules) {}

    public function definition(string $module): ?array
    {
        return $this->modules[$module] ?? null;
    }

    public function all(): array
    {
        return $this->modules;
    }

    public function enabled(string $module): bool
    {
        if (! array_key_exists($module, $this->modules)) {
            return false;
        }

        $override = $this->databaseOverride($module);
        if ($override !== null) {
            return $override;
        }

        return (bool) ($this->modules[$module]['enabled'] ?? false);
    }

    public function enabledModules(): array
    {
        return array_values(array_filter(
            array_keys($this->modules),
            fn (string $name): bool => $this->enabled($name),
        ));
    }

    public function isCore(string $module): bool
    {
        return (bool) ($this->modules[$module]['core'] ?? false);
    }

    /** @return list<string> */
    public function dependencies(string $module): array
    {
        $depends = $this->modules[$module]['depends_on'] ?? [];

        return array_values(array_unique(array_map('strval', $depends)));
    }

    /**
     * Ensure every enabled module has its dependencies enabled.
     *
     * @throws RuntimeException
     */
    public function assertDependenciesSatisfied(): void
    {
        foreach ($this->enabledModules() as $module) {
            foreach ($this->dependencies($module) as $dependency) {
                if (! $this->enabled($dependency)) {
                    throw new RuntimeException(
                        "Module [{$module}] depends on [{$dependency}], which is disabled."
                    );
                }
            }
        }
    }

    /**
     * @throws RuntimeException when disabling would break a core module or dependent module
     */
    public function assertCanDisable(string $module): void
    {
        if (! array_key_exists($module, $this->modules)) {
            throw new RuntimeException("Unknown module [{$module}].");
        }

        if ($this->isCore($module)) {
            throw new RuntimeException("Core module [{$module}] cannot be disabled.");
        }

        foreach ($this->enabledModules() as $other) {
            if ($other === $module) {
                continue;
            }

            if (in_array($module, $this->dependencies($other), true)) {
                throw new RuntimeException(
                    "Cannot disable [{$module}] while [{$other}] depends on it."
                );
            }
        }
    }

    public function registerEnabledProviders(Application $app): void
    {
        $this->assertDependenciesSatisfied();

        foreach ($this->modules as $name => $definition) {
            if (! $this->enabled($name)) {
                continue;
            }

            $provider = $definition['provider'] ?? null;
            if (! is_string($provider) || ! class_exists($provider)) {
                throw new RuntimeException("Enabled module [{$name}] has no valid service provider.");
            }

            $app->register($provider);
        }
    }

    private function databaseOverride(string $module): ?bool
    {
        // May run during provider registration before the DB/Eloquent resolver is ready.
        try {
            if (! Schema::hasTable('module_settings')) {
                return null;
            }

            $setting = ModuleSetting::query()->where('module_key', $module)->first();

            return $setting ? (bool) $setting->is_enabled : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
