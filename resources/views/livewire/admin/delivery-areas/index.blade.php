<section>
    @if (session('status'))
        <div style="padding:12px 15px;border-radius:10px;background:#e5f4e9;color:#205d3b;margin-bottom:14px">{{ session('status') }}</div>
    @endif
    <div class="panel" style="margin-bottom:18px">
        <h2>{{ $editingId ? 'تعديل منطقة توصيل' : 'إضافة منطقة توصيل جديدة' }}</h2>
        <form wire:submit="save" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px">
            @if($isSystemAdmin)
                <label style="font-size:13px">الفرع
                    <select wire:model="branchId" required style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px;background:#fff">
                        @foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach
                    </select>
                </label>
            @endif
            <label style="font-size:13px">المحافظة
                <select wire:model="governorate" required style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px;background:#fff">
                    @foreach(\App\Support\Delivery\KuwaitAreas::governorates() as $governorateOption)
                        <option value="{{ $governorateOption }}">{{ $governorateOption }}</option>
                    @endforeach
                </select>
                @error('governorate')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label style="font-size:13px">اسم المنطقة
                <input wire:model="name" required maxlength="150" placeholder="مثال: الخالدية" style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px">
                @error('name')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label style="font-size:13px">رسوم التوصيل (د.ك)
                <input wire:model="deliveryFee" type="number" min="0" step="0.001" required style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px">
                @error('deliveryFee')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label style="font-size:13px">الحد الأدنى للطلب (د.ك)
                <input wire:model="minimumOrder" type="number" min="0" step="0.001" required style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px">
                @error('minimumOrder')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label style="font-size:13px">ترتيب الظهور
                <input wire:model="sortOrder" type="number" min="0" step="1" style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px">
            </label>
            <label style="display:flex;align-items:center;gap:9px;font-size:13px"><input wire:model="isActive" type="checkbox"> المنطقة متاحة للتوصيل</label>
            <div style="display:flex;gap:8px;align-items:center">
                <button type="submit" wire:loading.attr="disabled" style="border:0;border-radius:9px;padding:11px 18px;background:#1d5b3d;color:#fff;font:inherit;cursor:pointer">حفظ المنطقة</button>
                @if($editingId)<button type="button" wire:click="resetForm" style="border:1px solid #d8e3da;border-radius:9px;padding:10px 15px;background:#fff;font:inherit;cursor:pointer">إلغاء التعديل</button>@endif
            </div>
        </form>
    </div>
    <div class="panel">
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px">
            <input wire:model.live.debounce.300ms="search" placeholder="ابحث باسم المنطقة" style="flex:1;min-width:220px;padding:11px;border:1px solid #d8e3da;border-radius:9px">
            <select wire:model.live="governorateFilter" style="padding:11px;border:1px solid #d8e3da;border-radius:9px;background:#fff">
                <option value="">كل المحافظات</option>
                @foreach($governorates as $governorateOption)<option value="{{ $governorateOption }}">{{ $governorateOption }}</option>@endforeach
            </select>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>المحافظة</th><th>المنطقة</th><th>رسوم التوصيل</th><th>الحد الأدنى</th><th>الحالة</th><th>إجراءات</th></tr></thead>
                <tbody>
                @forelse($areas as $area)
                    <tr wire:key="delivery-area-{{ $area->id }}">
                        <td>{{ $area->governorate }}</td>
                        <td><strong>{{ $area->name }}</strong></td>
                        <td>{{ $area->delivery_fee }} د.ك</td>
                        <td>{{ $area->minimum_order }} د.ك</td>
                        <td><span class="status">{{ $area->is_active ? 'متاحة' : 'متوقفة' }}</span></td>
                        <td><div style="display:flex;gap:6px;flex-wrap:wrap">
                            <button wire:click="edit({{ $area->id }})" style="border:0;border-radius:8px;padding:7px 10px;background:#edf3f8;color:#315b83;cursor:pointer;font:inherit;font-size:12px">تعديل</button>
                            <button wire:click="toggleActive({{ $area->id }})" wire:confirm="تأكيد تغيير حالة المنطقة؟" style="border:0;border-radius:8px;padding:7px 10px;background:#f0f4ef;color:#42664c;cursor:pointer;font:inherit;font-size:12px">{{ $area->is_active ? 'إيقاف' : 'تفعيل' }}</button>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty">لم تتم إضافة مناطق توصيل بعد</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding-top:16px">{{ $areas->links() }}</div>
    </div>
</section>
