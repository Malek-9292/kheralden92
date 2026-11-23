# النموذج المنطقي الأولي للبيانات

| الحقل | القيمة |
|---|---|
| المعرّف | FRS-DATA-001 |
| الإصدار | 0.1 |
| الحالة | نموذج منطقي أولي — غير معتمد للتنفيذ النهائي |

## قواعد عامة
- MySQL 8 وInnoDB وutf8mb4.
- مفاتيح أساسية داخلية، وUUID/ULID عام للموارد التي تظهر في الروابط أو واجهات API.
- جميع المبالغ DECIMAL(12,3) بالدينار الكويتي.
- حفظ التوقيتات مع UTC، وعرضها بتوقيت Asia/Kuwait أو توقيت الفرع.
- استخدام soft delete حيث يلزم، وعدم حذف الطلبات والفواتير نهائيًا.
- كل جدول تشغيلي متعدد الفروع يحمل branch_id ويطبق عليه نطاق الفرع.

## المجالات والكيانات الأساسية

### الفروع والهوية
- branches: code, name, address, timezone, is_active.
- users: branch_id, name, phone, email, password_hash, status, valid_from, valid_until.
- roles / permissions / role_user / permission_role: أدوار وصلاحيات مرنة.
- user_sessions: جلسات الموظف وأوقات الدخول والخروج والإبطال.
- login_attempts: IP ووقت المحاولة وبيانات تقريبية عند توفرها.
- audit_logs: الفاعل والفرع والعملية والكيان والقيم السابقة واللاحقة والسبب والتوقيت.

### الكتالوج
- categories: branch_id, parent_id, name, sort_order, is_active.
- products: branch_id, category_id, name, description, price, is_active, availability_state.
- product_images: product_id, path, is_primary, sort_order.
- product_options / product_option_values: الإضافات والاستبدالات والاختيارات.
- product_price_history: السعر السابق والجديد والسبب والموظف والتوقيت.
- product_availability_windows: فترات الظهور اليومية.

### الطلبات والعملاء
- customers: الاسم والهاتف والبريد الاختياري وحالة التحقق.
- customer_addresses: customer_id, governorate, area, block, street, avenue, house, floor, apartment, notes, landmark.
- carts / cart_items: سلة العميل وبنودها وأسعارها المعروضة وقت الإضافة.
- orders: branch_id, order_number, customer_id, order_type, status_internal, payment_method, currency, subtotal, discount_total, delivery_fee, grand_total, customer_note.
- order_items: اسم الصنف وسعره وكمّيته ولقطة الخيارات والملاحظات وقت الطلب.
- order_events: الأحداث الزمنية والفاعل والسبب.
- order_cancellations / order_rejections: السبب والمنفذ والتوقيت.
- order_invoices: لقطة الفاتورة الثابتة المرتبطة بالطلب.

### التوصيل والدفع والتحصيل
- delivery_areas: branch_id, name, fee, minimum_order, is_active.
- delivery_area_groups: مجموعات المناطق والحدود الافتراضية.
- delivery_addresses: لقطة عنوان الطلب وقت الإنشاء.
- cash_collections: المبلغ المستلم والباقي وموظف التحصيل والتوقيت.
- لا توجد جداول لبوابة دفع أو بطاقات أو حالات دفع إلكتروني في P0.

### الورديات والكاشير
- shifts: branch_id, cashier_id, opened_at, opening_float, closed_at, expected_cash, actual_cash, variance.
- shift_transactions: المقبوضات والحركات النقدية المسموح بها؛ لا تُستخدم لتسجيل مصروفات تشغيلية.
- cashier_preferences: تفضيلات العرض والفرز والاختصارات.

### الطباعة
- printers: الفرع، الاسم، النوع، الاتصال، التفعيل، الدور الافتراضي.
- print_jobs: document_type, order_id, printer_id, status, attempts, idempotency_key, requested_at, completed_at.
- print_job_attempts: النتيجة ورسالة الخطأ ووقت المحاولة.
- print_reprints: المستخدم والتوقيت ونوع المستند وعدد النسخ.

### العروض والإشعارات والمحتوى
- promotions / coupons / coupon_redemptions.
- notifications / notification_preferences / notification_reads.
- banners / content_pages / faqs / theme_settings.
- customer_inquiries: استفسار عام دون دورة إدارة شكاوى مستقلة ما لم يعتمد قرار جديد.

### النسخ الاحتياطي والتشغيل
- backup_runs: نوع النسخة والموقع والحالة والحجم والتشفير ووقت التنفيذ ونتيجة اختبار السلامة.
- system_health_checks: مكون الخدمة وحالتها ووقت الفحص.
- deployment_records: الإصدار ونتائج الاختبارات والاعتماد والتوقيت.

## علاقات وقيود حاسمة
- الطلب يحتفظ بلقطة اسم الصنف وسعره وخياراته حتى لو تغير الكتالوج لاحقًا.
- عنوان الطلب لقطة ثابتة ولا يتغير بتعديل العميل لعناوينه المحفوظة.
- لا يقبل طلب توصيل دون منطقة صالحة ورسوم وحد أدنى متحقق منهما على الخادم.
- لا يقبل الطلب عند إغلاق استقبال الطلبات.
- كل عملية إلغاء أو رفض أو تغيير دفع أو إعادة طباعة لها سجل تدقيق.
- قيود استخدام الكوبون يجب أن تكون ذرية لمنع تجاوز الحدود مع الطلبات المتزامنة.
- كل مهمة طباعة لها مفتاح idempotency لمنع تكرار تنفيذها.
- جداول المخزون والموردين والمشتريات والمصروفات مستبعدة من هذا النموذج في الإصدار الحالي.

هذا النموذج لا يتحول إلى migrations قبل مراجعة العلاقات والقيود والفهارس وموافقة مالك المشروع.
