# جاهزية الإنتاج — فتوش الخالدية (Phase 9)

## لا يُنفَّذ دون موافقة صريحة من المالك
- لا دمج تلقائي يتجاوز سياسة المالك (حاليًا: الدمج مسموح عند CI أخضر).
- **لا نشر إنتاج** و**لا شراء استضافة** دون طلب منفصل صريح.
- لا كلمات مرور إدارية ثابتة في المستودع؛ استخدم `php artisan fatoosh:admin:create`.

## قائمة تحقق أمنية
| بند | الحالة في الكود |
|---|---|
| HTTPS في الإنتاج | يُفرض عند النشر (اضبط `APP_URL` وTLS على الوكيل) |
| جلسات الموظفين + مهلة خمول | موجودة (`config/security.php`) |
| عدم تسريب رمز الطابعة / رمز التتبع | الرموز تُعرض مرة؛ التخزين hashed |
| Idempotent checkout | مفتاح idempotency على الطلبات |
| صلاحيات التقارير/الطلبات/الكاشير | BranchAccess + permissions |
| أسرار خارج Git | `.env` متجاهل؛ لا أسرار في المستودع |
| Pint + Feature tests في CI | `.github/workflows/ci.yml` |

## نشر مقترح (staging أولًا)
1. خادم PHP 8.2+/8.3، MySQL 8، امتدادات Laravel المعتادة.
2. `composer install --no-dev --optimize-autoloader`
3. انسخ `.env.example` → `.env` وعبّئ DB و`APP_KEY` و`APP_URL`.
4. `php artisan migrate --force`
5. `php artisan db:seed --force` (أو تشغيل Catalog/Delivery seeders فقط على بيئة جديدة)
6. `php artisan storage:link`
7. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
8. طابور اختياري: `php artisan queue:work` إن فُعّلت الطوابير.
9. سجّل طابعة مطبخ وكاشير من `/admin/printers` وانسخ رمز الجهاز إلى Print Agent فقط.
10. أنشئ المدير بـ `php artisan fatoosh:admin:create` تفاعليًا.

## اختبارات انحدار قبل Go-Live
- تسجيل دخول موظف + رفض جلسة ملغاة
- منيو عميل + سلة + استلام/توصيل + تتبع
- دورة طلب: قبول → تجهيز → جاهز → (توصيل) → تحصيل نقدي مرة واحدة
- سحب مهمة طباعة عبر API (ثم اختبار USB الفعلي على Epson TM-T20)
- تقرير يومي بفرع وفلتر صلاحيات
- `vendor/bin/pint --test` و`php artisan test` أخضران على الفرع

## بوابات مفتوحة (لا تغلق الإطلاق)
1. اختبار Epson TM-T20 فعلي (DEC-012).
2. اختبار تثبيت PWA على أجهزة حقيقية.
3. موافقة المالك على نشر الإنتاج بعد staging ناجح.
