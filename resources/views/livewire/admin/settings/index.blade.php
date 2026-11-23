<section>
    @if(session('status'))<div style="padding:12px 15px;border-radius:10px;background:#e5f4e9;color:#205d3b;margin-bottom:14px">{{ session('status') }}</div>@endif
    <div class="panel">
        <h2>هوية المطعم وبيانات التواصل</h2>
        <p class="muted">الإعدادات مستقلة لكل فرع، وتظهر على واجهة العميل بعد الحفظ.</p>
        <form wire:submit="save" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
            @if($isSystemAdmin)
                <label class="field full">الفرع
                    <select wire:model.live="branchId" required>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select>
                </label>
            @endif
            <label class="field">اسم المطعم
                <input wire:model="restaurantName" required maxlength="150">
                @error('restaurantName')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label class="field">الشعار النصي
                <input wire:model="tagline" maxlength="250">
            </label>
            <label class="field">رقم الهاتف
                <input wire:model="phone" type="tel" maxlength="30" dir="ltr">
            </label>
            <label class="field">رقم واتساب
                <input wire:model="whatsapp" type="tel" maxlength="30" dir="ltr">
            </label>
            <label class="field">لون الهوية الأساسي
                <div style="display:flex;gap:9px;align-items:center"><input wire:model.live="primaryColor" type="color" style="width:55px;height:40px;padding:2px"><input wire:model="primaryColor" dir="ltr" maxlength="7"></div>
                @error('primaryColor')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label class="field">اللون المساعد
                <div style="display:flex;gap:9px;align-items:center"><input wire:model.live="accentColor" type="color" style="width:55px;height:40px;padding:2px"><input wire:model="accentColor" dir="ltr" maxlength="7"></div>
                @error('accentColor')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label class="field full">شعار المطعم (PNG / JPG / WEBP حتى 2 ميجابايت)
                <input wire:model="logo" type="file" accept=".jpg,.jpeg,.png,.webp,image/*">
                @error('logo')<small style="color:#b42318">{{ $message }}</small>@enderror
                @if($logo)<img src="{{ $logo->temporaryUrl() }}" alt="معاينة الشعار" style="width:110px;height:90px;object-fit:contain;background:#f4f7f4;border-radius:10px;padding:8px">@elseif($currentLogoUrl)<img src="{{ $currentLogoUrl }}" alt="شعار المطعم الحالي" style="width:110px;height:90px;object-fit:contain;background:#f4f7f4;border-radius:10px;padding:8px">@endif
            </label>

            <div class="field full" style="border-top:1px solid #edf1ed;padding-top:14px">
                <strong>صندوق الهوية أعلى الواجهة</strong>
                <p class="muted" style="margin:6px 0 0;line-height:1.7">تحكّم بلون خلفية صندوق الشعار والاسم والخط والحدود كما تظهر للعميل في أعلى الصفحة.</p>
            </div>
            <label class="field">خلفية صندوق الهوية
                <div style="display:flex;gap:9px;align-items:center"><input wire:model.live="brandBoxBackground" type="color" style="width:55px;height:40px;padding:2px"><input wire:model="brandBoxBackground" dir="ltr" maxlength="7"></div>
                @error('brandBoxBackground')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label class="field">لون حدود الصندوق
                <div style="display:flex;gap:9px;align-items:center"><input wire:model.live="brandBoxBorderColor" type="color" style="width:55px;height:40px;padding:2px"><input wire:model="brandBoxBorderColor" dir="ltr" maxlength="7"></div>
                @error('brandBoxBorderColor')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label class="field">لون اسم المطعم
                <div style="display:flex;gap:9px;align-items:center"><input wire:model.live="brandNameColor" type="color" style="width:55px;height:40px;padding:2px"><input wire:model="brandNameColor" dir="ltr" maxlength="7"></div>
                @error('brandNameColor')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label class="field">لون الشعار النصي
                <div style="display:flex;gap:9px;align-items:center"><input wire:model.live="brandTaglineColor" type="color" style="width:55px;height:40px;padding:2px"><input wire:model="brandTaglineColor" dir="ltr" maxlength="7"></div>
                @error('brandTaglineColor')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label class="field">خلفية إطار الشعار
                <div style="display:flex;gap:9px;align-items:center"><input wire:model.live="brandLogoBackground" type="color" style="width:55px;height:40px;padding:2px"><input wire:model="brandLogoBackground" dir="ltr" maxlength="7"></div>
                @error('brandLogoBackground')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>

            <div class="field full" style="border-top:1px solid #edf1ed;padding-top:14px">
                <strong>كتلة الترحيب</strong>
                <p class="muted" style="margin:6px 0 0;line-height:1.7">تشمل تنبيه تثبيت التطبيق على iPhone وبانر الترحيب «أهلاً بيك…». يمكن إخفاؤها لإبقاء الواجهة أنظف.</p>
            </div>
            <label class="field full" style="display:flex;align-items:flex-start;gap:10px">
                <input wire:model.live="showStorefrontTopBlock" type="checkbox" style="margin-top:4px">
                <span>إظهار كتلة الترحيب أعلى المنيو</span>
            </label>

            <div class="field full" style="border-top:1px solid #edf1ed;padding-top:14px">
                <strong>البانر الرئيسي</strong>
                <p class="muted" style="margin:6px 0 0;line-height:1.7">يظهر داخل كتلة الترحيب عند تفعيلها أعلاه.</p>
            </div>
            <label class="field full">نص البانر
                <input wire:model="bannerText" maxlength="500" placeholder="عروض فتوش الخالدية">
            </label>
            <label class="field">لون نص البانر
                <div style="display:flex;gap:9px;align-items:center"><input wire:model.live="bannerTextColor" type="color" style="width:55px;height:40px;padding:2px"><input wire:model="bannerTextColor" dir="ltr" maxlength="7"></div>
            </label>
            <label class="field">خلفية البانر
                <div style="display:flex;gap:9px;align-items:center"><input wire:model.live="bannerBackground" type="color" style="width:55px;height:40px;padding:2px"><input wire:model="bannerBackground" dir="ltr" maxlength="7"></div>
            </label>
            <label class="field">حجم خط البانر (12–48)
                <input wire:model="bannerFontSize" type="number" min="12" max="48">
                @error('bannerFontSize')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <div class="field full" style="border-top:1px solid #edf1ed;padding-top:14px">
                <strong>نص أسفل الموقع</strong>
            </div>
            <label class="field full">نص التذييل
                <textarea wire:model="footerText" rows="3" maxlength="500"></textarea>
            </label>
            <div class="field full" style="border-top:1px solid #edf1ed;padding-top:14px">
                <strong>وضع الطوارئ — التوصيل</strong>
                <p class="muted" style="margin:6px 0 0;line-height:1.7">عند التفعيل يُرفض طلب التوصيل من الواجهة والـ API، ويبقى الاستلام من المطعم متاحًا.</p>
            </div>
            <label class="field full" style="display:flex;align-items:flex-start;gap:10px">
                <input wire:model.live="deliveryPaused" type="checkbox" style="margin-top:4px">
                <span>إيقاف استقبال طلبات التوصيل مؤقتًا</span>
            </label>
            <label class="field full">سبب إيقاف التوصيل (يظهر للعميل)
                <input wire:model="deliveryPauseReason" maxlength="250" placeholder="مثال: ضغط الطلبات، ظروف الطقس، نقص سائقين">
                @error('deliveryPauseReason')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <div class="field full" style="display:flex;flex-direction:row;gap:10px;align-items:center">
                <button type="submit" wire:loading.attr="disabled" class="primary">حفظ الإعدادات</button>
                <span class="muted" wire:loading wire:target="logo">جارٍ رفع الشعار...</span>
            </div>
        </form>
    </div>
</section>
