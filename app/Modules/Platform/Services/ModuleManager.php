<?php

namespace App\Modules\Platform\Services;

use App\Models\SystemModule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;

class ModuleManager
{
    public function definitions(): array
    {
        return config('modules', []);
    }

    public function isEnabled(string $code): bool
    {
        $definition = $this->definitions()[$code] ?? null;

        if (! is_array($definition)) {
            return false;
        }

        if (! Schema::hasTable('system_modules')) {
            return (bool) ($definition['enabled_by_default'] ?? false);
        }

        $stored = SystemModule::query()->where('code', $code)->value('is_enabled');

        return $stored === null
            ? (bool) ($definition['enabled_by_default'] ?? false)
            : (bool) $stored;
    }

    public function syncDefaults(): void
    {
        if (! Schema::hasTable('system_modules')) {
            return;
        }

        foreach ($this->definitions() as $code => $definition) {
            SystemModule::query()->firstOrCreate(
                ['code' => $code],
                [
                    'name' => $definition['name'],
                    'is_enabled' => $definition['enabled_by_default'] ?? false,
                    'is_core' => $definition['core'] ?? false,
                    'dependencies' => $definition['dependencies'] ?? [],
                ],
            );
        }
    }

    public function enable(string $code): void
    {
        $definition = $this->definition($code);

        DB::transaction(function () use ($code, $definition): void {
            foreach ($definition['dependencies'] ?? [] as $dependency) {
                if (! $this->isEnabled($dependency)) {
                    throw new LogicException("Cannot enable [{$code}]: dependency [{$dependency}] is disabled.");
                }
            }

            SystemModule::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $definition['name'],
                    'is_enabled' => true,
                    'is_core' => $definition['core'] ?? false,
                    'dependencies' => $definition['dependencies'] ?? [],
                ],
            );
        });
    }

    public function disable(string $code): void
    {
        $definition = $this->definition($code);

        if (($definition['core'] ?? false) === true) {
            throw new LogicException("Core module [{$code}] cannot be disabled.");
        }

        DB::transaction(function () use ($code): void {
            foreach ($this->definitions() as $dependentCode => $dependent) {
                if (
                    $dependentCode !== $code
                    && $this->isEnabled($dependentCode)
                    && in_array($code, $dependent['dependencies'] ?? [], true)
                ) {
                    throw new LogicException(
                        "Cannot disable [{$code}]: enabled module [{$dependentCode}] depends on it."
                    );
                }
            }

            SystemModule::query()->where('code', $code)->update(['is_enabled' => false]);
        });
    }

    private function definition(string $code): array
    {
        $definition = $this->definitions()[$code] ?? null;

        if (! is_array($definition)) {
            throw new InvalidArgumentException("Unknown module [{$code}].");
        }

        return $definition;
    }
}
