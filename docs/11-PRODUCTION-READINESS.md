# جاهزية الإنتاج — فتوش الخالدية (Phase 9)

## لا يُنفَّذ دون موافقة صريحة من المالك
- لا دمج تلقائي يتجاوز سياسة المالك (حاليًا: الدمج مسموح عند CI أخضر).
- **لا نشر إنتاج** و**لا شراء استضافة** دون طلب منفصل صريح.
- لا كلمات مرور إدارية ثابتة في المستودع؛ استخدم `php artisan fatoosh:admin:create`.

## قائمة تحقق أمنية
| بند | الحالة في الكود |
|---|---|
| HTTPS في الإنتاج / staging | `SecureApplication` يفرض HTTPS عند `FORCE_HTTPS=true` أو `APP_ENV=production` مع `APP_URL` على https؛ يثق بالوكيل عبر `trustProxies` |
| رؤوس أمان أساسية | `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, و`HSTS` عند HTTPS |
| جلسات الموظفين + مهلة خمول | موجودة (`config/security.php` + `.env` idle minutes) |
| عدم تسريب رمز الطابعة / رمز التتبع | الرموز تُعرض مرة؛ التخزين hashed |
| Idempotent checkout | مفتاح idempotency على الطلبات |
| Idempotent print complete/fail | Print Agent API يتجاهل التأكيدات المكررة بأمان |
| صلاحيات التقارير/الطلبات/الكاشير | BranchAccess + permissions |
| أسرار خارج Git | `.env` متجاهل؛ لا أسرار في المستودع |
| Pint + Feature tests في CI | `.github/workflows/ci.yml` |

---

## Runbook — Staging فقط (بدون شراء استضافة هنا)

هذا الدليل يفترض أن المالك وفّر خادم staging بنفسه. الوكيل **لا يشتري** استضافة ولا ينشر إنتاجًا.

### المتطلبات على الخادم
- PHP 8.2+ أو 8.3 مع امتدادات Laravel المعتادة (`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`)
- MySQL 8
- Composer 2
- خادم ويب: **جذر المستند = `public/`** مع إعادة كتابة كل الطلبات إلى `public/index.php` (nginx `try_files` أو Apache `AllowOverride` + `.htaccess`)
- اختياري: Redis 6/7 + امتداد `redis` (phpredis) **أو** حزمة `predis/predis` إن رغبت بـ Redis لاحقًا
- شهادة TLS (Let’s Encrypt أو ما يكافئها) لأن Print Agent يرفض غير HTTPS

### إنشاء قاعدة البيانات (مرة واحدة)
```sql
CREATE DATABASE fatoosh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'fatoosh'@'localhost' IDENTIFIED BY 'change-me';
GRANT ALL PRIVILEGES ON fatoosh.* TO 'fatoosh'@'localhost';
FLUSH PRIVILEGES;
```

### خطوات النشر على Staging
1. انسخ المستودع إلى الخادم على فرع `main` (أو فرع موافق عليه).
2. `composer install --no-dev --optimize-autoloader`
3. انسخ `.env.example` → `.env` على الخادم فقط، ثم:
   - `chmod 600 .env`
   - ولّد مفتاحًا جديدًا: `php artisan key:generate` (**لا تنسخ `APP_KEY` من جهاز محلّي**)
   - عبّئ: `APP_ENV=staging`، `APP_DEBUG=false`، `APP_URL=https://staging.example.com`، `DB_*`، `FORCE_HTTPS=true`، `SESSION_SECURE_COOKIE=true`
4. اترك افتراضيًا: `SESSION_DRIVER=database` و`CACHE_STORE=database` و`QUEUE_CONNECTION=database`
5. إن وُجد Redis جاهز ومعزول لـ staging فقط، يمكنك لاحقًا تحويل الثلاثة إلى `redis` وملء `REDIS_*` (انظر قسم Redis). **لا تشارك Redis مع الإنتاج.**
6. `php artisan migrate --force`
7. على بيئة جديدة فقط: `php artisan db:seed --force` (يشمل كتالوج فتوش + مناطق الكويت). لا تعِد الـ seed على بيانات حية دون نسخة احتياطية.
8. `php artisan storage:link` — بدون هذا الرابط ستظهر صور الأصناف 404 من `/storage/...`
9. امنح مستخدم php-fpm ملكية/كتابة على `storage/` و`bootstrap/cache/`
10. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
11. طابور خلفي دائم (اختياري ما دامت Jobs قليلة): وحدة systemd/supervisor تشغّل  
    `php artisan queue:work --sleep=1 --tries=3` وتُعاد تلقائيًا عند الخروج — لا تعتمد على جلسة SSH لمرة واحدة.
12. أنشئ المدير تفاعليًا: `php artisan fatoosh:admin:create`
13. سجّل طابعة مطبخ من `/admin/printers` وانسخ رمز الجهاز إلى Print Agent فقط (لا تضعه في Git).
14. افحص `/up` ثم الصفحة العامة `/` ولوحة `/admin/login`.

### مثال nginx (مختصر)
```nginx
root /var/www/fatoosh/public;
index index.php;
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
location ~ \.php$ {
    include fastcgi_params;
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
}
```
اضبط المسارات وإصدار PHP حسب الخادم. الوكيل يجب أن يمرّر `X-Forwarded-Proto` لأن التطبيق يثق بالوكلاء لفرض HTTPS.

### تحقق سريع بعد Staging
- [ ] `/up` = 200
- [ ] المتصفح يفتح على HTTPS دون تحذير شهادة
- [ ] رؤوس الأمان موجودة (DevTools → Network)
- [ ] صور المنيو تُحمَّل من `/storage/...` بعد `storage:link`
- [ ] تسجيل دخول موظف يعمل؛ جلسة ملغاة تُرفض
- [ ] المنيو يظهر كاملًا (أكثر من 100 صنف بعد الـ seed)
- [ ] طلب استلام + تتبع
- [ ] طلب توصيل منطقة الخالدية برسوم 0.250
- [ ] مهمة طباعة تُسحب عبر API (العناد الفعلي مؤجّل بقرار المالك)
- [ ] `vendor/bin/pint --test` و`php artisan test` أخضران على الفرع قبل الدمج

### Rollback سريع (Staging)
1. أعد نشر الـ commit السابق المعروف الاستقرار.
2. إن كانت هناك هجرة مدمّرة: استعد من نسخة DB الاحتياطية (انظر `docs/10-BACKUP-RESTORE.md` — الاستعادة للمطوّر فقط DEC-090).
3. امسح الكاش: `php artisan optimize:clear`

---

## Redis (اختياري — غير مطلوب للإطلاق الأولي)
- الافتراضي الآمن في `.env.example`: session/cache/queue على **database** (لا حاجة لـ Redis للتشغيل).
- مفاتيح `REDIS_HOST` / `REDIS_PORT` / `REDIS_PASSWORD` / `REDIS_DB` / `REDIS_CACHE_DB` / `REDIS_CLIENT` موجودة.
- `config/database.php` يعرّف اتصالَي `default` و`cache` (الكاش يستخدم `REDIS_CACHE_DB`، الافتراضي 1).
- للتفعيل على staging بعد توفر Redis:
  1. ثبّت امتداد phpredis **أو** `composer require predis/predis` ثم `REDIS_CLIENT=predis`.
  2. اضبط `SESSION_DRIVER=redis` و`CACHE_STORE=redis` و`QUEUE_CONNECTION=redis`.
  3. افصل فهارس DB بين البيئات (`REDIS_DB` للجلسات/الطابور، `REDIS_CACHE_DB` للكاش). Laravel يضيف prefix من `APP_NAME`.
  4. أعد `config:cache` واختبر تسجيل الدخول + صفحة المنيو + إنشاء طلب.

لا تجعل حفظ الطلب معتمدًا على Redis؛ الطلبات تُكتب في MySQL أولًا.

---

## اختبارات انحدار قبل Go-Live
- تسجيل دخول موظف + رفض جلسة ملغاة
- منيو عميل + سلة + استلام/توصيل + تتبع
- دورة طلب: قبول → تجهيز → جاهز → (توصيل) → تحصيل نقدي مرة واحدة
- سحب مهمة طباعة عبر API (ثم اختبار USB الفعلي على Epson TM-T20 عندما يقرر المالك)
- تقرير يومي بفرع وفلتر صلاحيات
- `vendor/bin/pint --test` و`php artisan test` أخضران على الفرع

## بوابات مفتوحة (لا تغلق الإطلاق)
1. اختبار Epson TM-T20 فعلي (DEC-012) — **مؤجّل بقرار المالك**.
2. اختبار تثبيت PWA على أجهزة حقيقية.
3. موافقة المالك على نشر الإنتاج بعد staging ناجح (طلب منفصل؛ لا شراء استضافة من الوكيل).
