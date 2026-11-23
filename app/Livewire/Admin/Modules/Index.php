<?php

namespace App\Livewire\Admin\Modules;

use App\Models\AuditLog;
use App\Models\ModuleSetting;
use App\Support\Modules\ModuleManager;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use RuntimeException;

class Index extends Component
{
    public string $moduleError = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('modules.manage'), 403);
    }

    public function toggle(string $module): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermissionTo('modules.manage'), 403);

        /** @var ModuleManager $manager */
        $manager = app(ModuleManager::class);
        $definition = $manager->definition($module);
        abort_if($definition === null, 404);

        $current = $manager->enabled($module);

        if ($current === false && ($definition['implemented'] ?? true) === false) {
            $this->moduleError = 'هذه الوحدة قيد التطوير ولم تُنفذ بعد.';

            return;
        }

        if ($current) {
            try {
                $manager->assertCanDisable($module);
            } catch (RuntimeException $exception) {
                $this->moduleError = str_starts_with($exception->getMessage(), 'Core module')
                    ? 'الوحدة الأساسية لا يمكن إيقافها.'
                    : 'لا يمكن إيقاف هذه الوحدة لأنها مطلوبة لوحدة أخرى مفعلة.';

                return;
            }
        } else {
            $disabledDependencies = array_values(array_filter(
                $manager->dependencies($module),
                fn (string $dependency): bool => $manager->enabled($dependency) === false,
            ));

            if ($disabledDependencies !== []) {
                $this->moduleError = 'يجب تفعيل الوحدات المطلوبة أولًا: '.implode('، ', $disabledDependencies);

                return;
            }
        }

        $next = $current === false;

        DB::transaction(function () use ($module, $next, $user): void {
            $setting = ModuleSetting::query()->updateOrCreate(
                ['module_key' => $module],
                ['is_enabled' => $next],
            );

            AuditLog::query()->create([
                'branch_id' => null,
                'actor_id' => $user->id,
                'action' => $next ? 'module.enabled' : 'module.disabled',
                'auditable_type' => ModuleSetting::class,
                'auditable_id' => $setting->id,
                'before_values' => ['is_enabled' => $current],
                'after_values' => ['is_enabled' => $next],
                'created_at' => now(),
            ]);
        });

        $this->moduleError = '';
        session()->flash('status', $next ? 'تم تفعيل الوحدة.' : 'تم إيقاف الوحدة. ستطبق الحالة على الطلب التالي.');
    }

    public function render()
    {
        /** @var ModuleManager $manager */
        $manager = app(ModuleManager::class);

        $modules = collect($manager->all())->map(function (array $definition, string $name) use ($manager): array {
            return [
                'name' => $name,
                'enabled' => $manager->enabled($name),
                'core' => $manager->isCore($name),
                'implemented' => $definition['implemented'] ?? true,
                'dependencies' => $manager->dependencies($name),
                'provider' => $definition['provider'] ?? '',
            ];
        })->values();

        return view('livewire.admin.modules.index', compact('modules'))
            ->layout('layouts.admin', ['heading' => 'مدير الوحدات']);
    }
}
