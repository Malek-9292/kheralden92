<?php
use Illuminate\Support\Facades\Artisan;
Artisan::command('modules:list', function (): void {
 foreach (app(App\Support\Modules\ModuleManager::class)->enabledModules() as $module) $this->line($module);
})->purpose('List enabled application modules');

Artisan::command('fatoosh:admin:create', function (): void {
    $adminRole = App\Models\Role::query()->where('slug', 'system_admin')->whereNull('branch_id')->first();

    if (! $adminRole) {
        $this->error('شغّل migrations ثم db:seed أولًا.');
        return;
    }

    if (App\Models\User::query()->whereHas('roles', fn ($query) => $query->where('slug', 'system_admin'))->exists()) {
        $this->error('يوجد مدير نظام رئيسي بالفعل. لن يتم إنشاء حساب إضافي بهذا الأمر.');
        return;
    }

    $name = $this->ask('اسم المدير');
    $phone = $this->ask('رقم الهاتف مع مفتاح الدولة');
    $password = $this->secret('كلمة المرور');
    $confirmation = $this->secret('تأكيد كلمة المرور');

    if (! $name || ! $phone || strlen((string) $password) < 12 || $password !== $confirmation) {
        $this->error('البيانات غير مكتملة، أو كلمة المرور أقل من 12 حرفًا، أو التأكيد غير مطابق.');
        return;
    }

    $user = App\Models\User::query()->create([
        'name' => $name,
        'phone' => $phone,
        'password' => Illuminate\Support\Facades\Hash::make($password),
        'status' => 'active',
        'branch_id' => null,
    ]);

    $user->roles()->attach($adminRole->id);
    $this->info('تم إنشاء حساب مدير النظام الرئيسي. احتفظ ببيانات الدخول في مكان آمن.');
})->purpose('Create the first Fatoosh system administrator interactively');
