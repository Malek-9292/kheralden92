# نظام فتوش الخالدية

تطبيق جديد مستقل لإدارة وتشغيل مطعم فتوش الخالدية، ولا يتصل بأي مشروع أو قاعدة بيانات أو خدمة قديمة.

## التقنية
- PHP 8.3+ / Laravel 13 / Livewire 4
- MySQL 8.4 وRedis 7.4
- Modular Monolith، مع وحدات ذات حدود واعتماديات واضحة
- واجهة عربية RTL والمنطقة الزمنية Asia/Kuwait

## التشغيل المحلي
يتطلب PHP 8.3 وComposer 2 وNode.js وnpm وDocker Compose.

```bash
cp .env.example .env
composer install
php artisan key:generate
docker compose up -d
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

افتح http://127.0.0.1:8000. فحص الصحة: /up.

## الاختبارات
```bash
php artisan test
vendor/bin/pint --test
```
الاختبارات تستخدم SQLite in-memory ولا تتصل بقاعدة بيانات التطوير.

## الوحدات
توجد إدارة وحدات أولية عبر ModuleManager مع التحقق من الاعتماديات ومنع تعطيل الوحدات الأساسية أو الوحدات التي تعتمد عليها وحدات مفعلة. تعطيل الوحدة لا يعني حذف ملفاتها.

الفرع الأول هو فتوش الخالدية. المخزون والموردون والمشتريات والمصروفات مؤجلة وخارج التنفيذ الحالي.

## الأمان
لا ترفع .env أو أسرار حقيقية إلى GitHub. كلمات مرور Docker Compose مخصصة للتطوير المحلي فقط، ويجب تغييرها قبل أي بيئة مشتركة أو إنتاج.
