<section>
    @if(session('status'))<div style="padding:12px 15px;border-radius:10px;background:#e5f4e9;color:#205d3b;margin-bottom:14px">{{ session('status') }}</div>@endif
    @if($moduleError)<div style="padding:12px 15px;border-radius:10px;background:#fff2e8;color:#934b1b;margin-bottom:14px">{{ $moduleError }}</div>@endif
    <div class="panel" style="margin-bottom:16px">
        <strong>إدارة الوحدات بأمان</strong>
        <p class="muted">تفعيل أو إيقاف الوحدة لا يحذف ملفاتها أو بياناتها. الوحدات الأساسية لا يمكن إيقافها، ولا يمكن إيقاف وحدة تعتمد عليها وحدة أخرى مفعلة. تُطبق تغييرات التسجيل على الطلب التالي.</p>
    </div>
    @php($labels=['Identity'=>'الهوية وتسجيل الدخول','AccessControl'=>'الصلاحيات والتحكم','Branches'=>'الفروع','Audit'=>'سجل المراجعة','Catalog'=>'الأقسام والأصناف','Storefront'=>'واجهة العميل','Cart'=>'السلة','Orders'=>'الطلبات','Delivery'=>'التوصيل','Payments'=>'المدفوعات','Promotions'=>'العروض','Cashier'=>'الكاشير','Kitchen'=>'المطبخ','Printing'=>'الطباعة','Notifications'=>'الإشعارات','Reviews'=>'التقييمات','Reporting'=>'التقارير','Content'=>'المحتوى','CustomerService'=>'خدمة العملاء','BackupOps'=>'النسخ الاحتياطي','SystemSettings'=>'إعدادات النظام'])
    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px">
        @foreach($modules as $module)
            <div class="panel" wire:key="module-{{ $module['name'] }}">
                <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start">
                    <div>
                        <strong>{{ $labels[$module['name']] ?? $module['name'] }}</strong>
                        <div class="muted" dir="ltr">{{ $module['name'] }}</div>
                    </div>
                    <span class="status">{{ $module['core'] ? 'أساسية' : ($module['implemented'] ? ($module['enabled'] ? 'مفعلة' : 'متوقفة') : 'قيد التطوير') }}</span>
                </div>
                <div class="muted" style="margin-top:10px">المتطلبات: {{ count($module['dependencies']) ? implode('، ', $module['dependencies']) : 'لا يوجد' }}</div>
                <div class="muted" dir="ltr" style="overflow-wrap:anywhere;margin-top:5px;font-size:10px">{{ $module['provider'] }}</div>
                <div style="margin-top:14px">
                    @if($module['core'])
                        <button disabled class="secondary" style="opacity:.55;cursor:not-allowed">وحدة أساسية لا يمكن إيقافها</button>
                    @elseif(!$module['implemented'] && !$module['enabled'])
                        <button disabled class="secondary" style="opacity:.55;cursor:not-allowed">قيد التطوير</button>
                    @else
                        <button wire:click="toggle('{{ $module['name'] }}')" wire:confirm="{{ $module['enabled'] ? 'تأكيد إيقاف الوحدة؟' : 'تأكيد تفعيل الوحدة؟' }}" wire:loading.attr="disabled" class="{{ $module['enabled'] ? 'secondary' : 'primary' }}">
                            {{ $module['enabled'] ? 'إيقاف الوحدة' : 'تفعيل الوحدة' }}
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>
