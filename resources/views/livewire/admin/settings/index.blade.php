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
                <strong>البانر الرئيسي</strong>
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
            <div class="field full" style="border-top:1px solid #edf1ed;padding-top:14px">
                <strong>نص أسفل الموقع</strong>
            </div>
            <label class="field full">نص التذييل
                <textarea wire:model="footerText" rows="3" maxlength="500"></textarea>
            </label>
            <div class="field full" style="display:flex;flex-direction:row;gap:10px;align-items:center">
                <button type="submit" wire:loading.attr="disabled" class="primary">حفظ الإعدادات</button>
                <span class="muted" wire:loading wire:target="logo">جارٍ رفع الشعار...</span>
            </div>
        </form>
    </div>
</section>
