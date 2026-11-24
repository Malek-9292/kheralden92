<?php

use App\Models\Branch;
use App\Models\Printer;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Support\Facades\Artisan;

Artisan::command('modules:list', function (): void {
    foreach (app(ModuleManager::class)->enabledModules() as $module) {
        $this->line($module);
    }
})->purpose('List enabled application modules');

Artisan::command('fatoosh:admin:create', function (): void {
    $adminRole = Role::query()->where('slug', 'system_admin')->whereNull('branch_id')->first();

    if (! $adminRole) {
        $this->error('شغّل migrations ثم db:seed أولًا.');

        return;
    }

    if (User::query()->whereHas('roles', fn ($query) => $query->where('slug', 'system_admin'))->exists()) {
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

    // Plain password — User model casts password with "hashed".
    $user = User::query()->create([
        'name' => $name,
        'phone' => $phone,
        'password' => $password,
        'status' => 'active',
        'branch_id' => null,
    ]);

    $user->roles()->attach($adminRole->id);
    $this->info('تم إنشاء حساب مدير النظام الرئيسي. احتفظ ببيانات الدخول في مكان آمن.');
})->purpose('Create the first Fatoosh system administrator interactively');

Artisan::command('fatoosh:preview:bootstrap', function (): int {
    $this->call('db:seed', ['--force' => true]);

    $phone = trim((string) env('ADMIN_PHONE', ''));
    $password = (string) env('ADMIN_PASSWORD', '');

    if ($phone === '' || $password === '') {
        $this->error('ADMIN_PHONE و ADMIN_PASSWORD مطلوبان لتهيئة معاينة Render.');

        return 1;
    }

    if (strlen($password) < 12) {
        $this->error('ADMIN_PASSWORD يجب ألا تقل عن 12 حرفًا.');

        return 1;
    }

    $adminRole = Role::query()->where('slug', 'system_admin')->whereNull('branch_id')->first();
    if (! $adminRole) {
        $this->error('دور system_admin غير موجود بعد الـ seed.');

        return 1;
    }

    $user = User::query()->firstOrNew(['phone' => $phone]);
    $user->name = $user->exists ? $user->name : 'مدير النظام';
    $user->password = $password;
    $user->status = 'active';
    $user->branch_id = null;
    $user->save();
    $user->roles()->syncWithoutDetaching([$adminRole->id]);
    $this->info('تم تجهيز حساب المدير من متغيرات البيئة (بدون طباعة السر).');

    $printerToken = trim((string) env('PRINTER_TOKEN', ''));
    if ($printerToken === '') {
        $this->error('PRINTER_TOKEN مطلوب لطابعة KITCHEN-01 المستقرة عبر عمليات النشر.');

        return 1;
    }

    $branch = Branch::query()->where('code', 'KHAL')->first();
    if (! $branch) {
        $this->error('فرع KHAL غير موجود بعد الـ seed.');

        return 1;
    }

    Printer::query()->updateOrCreate(
        ['branch_id' => $branch->id, 'code' => 'KITCHEN-01'],
        [
            'name' => 'طابعة المطبخ',
            'purpose' => 'kitchen',
            'connection_type' => 'usb',
            'device_token_hash' => hash('sha256', $printerToken),
            'is_active' => true,
        ],
    );
    $this->info('تم تجهيز طابعة KITCHEN-01 من PRINTER_TOKEN.');

    $productCount = Product::query()->count();
    $this->info("معاينة جاهزة — عدد الأصناف: {$productCount}");

    return 0;
})->purpose('Idempotent preview bootstrap: catalog seed, admin from env, KITCHEN-01 printer');
