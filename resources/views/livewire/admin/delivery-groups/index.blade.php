<section>
    @if (session('status'))
        <div style="padding:12px 15px;border-radius:10px;background:#e5f4e9;color:#205d3b;margin-bottom:14px">{{ session('status') }}</div>
    @endif

    @if(auth()->user()->hasRole('system_admin'))
        <div class="panel" style="margin-bottom:16px">
            <label>الفرع
                <select wire:model.live="branchId" style="display:block;width:100%;max-width:360px;padding:10px;margin-top:6px;border:1px solid #d8e3da;border-radius:9px">
                    @foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach
                </select>
            </label>
        </div>
    @endif

    <div class="panel" style="margin-bottom:16px">
        <h2>{{ $editingGroupId ? 'تعديل مجموعة' : 'إنشاء مجموعة توصيل' }}</h2>
        <form wire:submit="saveGroup" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px">
            <label>اسم المجموعة
                <input wire:model="groupName" required maxlength="120" placeholder="مثال: مناطق العاصمة" style="display:block;width:100%;padding:10px;margin-top:6px;border:1px solid #d8e3da;border-radius:9px">
                @error('groupName')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label>رسوم التوصيل الافتراضية (د.ك)
                <input wire:model="deliveryFee" type="number" min="0" step="0.001" required style="display:block;width:100%;padding:10px;margin-top:6px;border:1px solid #d8e3da;border-radius:9px">
                @error('deliveryFee')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label>الحد الأدنى الافتراضي (د.ك)
                <input wire:model="minimumOrder" type="number" min="0" step="0.001" required style="display:block;width:100%;padding:10px;margin-top:6px;border:1px solid #d8e3da;border-radius:9px">
                @error('minimumOrder')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label style="display:flex;align-items:center;gap:8px"><input type="checkbox" wire:model="groupActive"> المجموعة مفعّلة</label>
            <div style="display:flex;gap:8px;align-items:center">
                <button type="submit" style="border:0;border-radius:9px;padding:10px 16px;background:#1d5b3d;color:white">حفظ المجموعة</button>
                @if($editingGroupId)<button type="button" wire:click="resetGroupForm" style="padding:9px 14px;border:1px solid #d8e3da;border-radius:9px;background:white">إلغاء</button>@endif
            </div>
        </form>
    </div>

    <div class="panel" style="margin-bottom:16px">
        <h2>المجموعات الحالية</h2>
        <div class="table-wrap"><table>
            <thead><tr><th>المجموعة</th><th>رسوم التوصيل</th><th>الحد الأدنى</th><th>عدد المناطق</th><th>الحالة</th><th></th></tr></thead>
            <tbody>
            @forelse($groups as $group)
                <tr wire:key="delivery-group-{{ $group->id }}">
                    <td><strong>{{ $group->name }}</strong></td>
                    <td>{{ $group->delivery_fee }} د.ك</td>
                    <td>{{ $group->minimum_order }} د.ك</td>
                    <td>{{ $group->areas_count }}</td>
                    <td>{{ $group->is_active ? 'مفعّلة' : 'متوقفة' }}</td>
                    <td><button wire:click="editGroup({{ $group->id }})" style="padding:7px 10px;border:0;border-radius:7px;background:#edf3f8">تعديل</button></td>
                </tr>
            @empty
                <tr><td colspan="6">لا توجد مجموعات حتى الآن.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>

    <div class="panel">
        <h2>ربط المناطق بالمجموعات والاستثناءات</h2>
        <p class="muted">كل منطقة تستخدم أسعار المجموعة تلقائيًا، ويمكن تفعيل أسعار خاصة لمنطقة واحدة.</p>
        <div class="table-wrap"><table>
            <thead><tr><th>المحافظة</th><th>المنطقة</th><th>المجموعة</th><th>السعر الفعلي</th><th></th></tr></thead>
            <tbody>
            @foreach($areas as $area)
                <tr wire:key="delivery-area-group-{{ $area->id }}">
                    <td>{{ $area->governorate }}</td><td><strong>{{ $area->name }}</strong></td>
                    <td>{{ $area->group?->name ?? 'غير مرتبطة' }}</td>
                    <td>{{ $area->effectiveDeliveryFee() }} د.ك / حد أدنى {{ $area->effectiveMinimumOrder() }} د.ك</td>
                    <td><button wire:click="selectArea({{ $area->id }})" style="padding:7px 10px;border:0;border-radius:7px;background:#edf3f8">إعدادات</button></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>

        @if($selectedAreaId)
            <div style="margin-top:18px;padding:16px;border:1px solid #d8e3da;border-radius:12px">
                <h3>إعدادات المنطقة: {{ $areas->firstWhere('id', $selectedAreaId)?->name }}</h3>
                <form wire:submit="saveAreaAssignment" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px">
                    <label>مجموعة التوصيل
                        <select wire:model="selectedGroupId" required style="display:block;width:100%;padding:10px;margin-top:6px;border:1px solid #d8e3da;border-radius:9px">
                            <option value="">اختر مجموعة</option>
                            @foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach
                        </select>
                        @error('selectedGroupId')<small style="color:#b42318">{{ $message }}</small>@enderror
                    </label>
                    <label style="display:flex;align-items:center;gap:8px"><input type="checkbox" wire:model.live="customPricing"> تفعيل أسعار مخصصة لهذه المنطقة</label>
                    @if($customPricing)
                        <label>رسوم التوصيل الخاصة (د.ك)
                            <input wire:model="customDeliveryFee" type="number" min="0" step="0.001" required style="display:block;width:100%;padding:10px;margin-top:6px;border:1px solid #d8e3da;border-radius:9px">
                            @error('customDeliveryFee')<small style="color:#b42318">{{ $message }}</small>@enderror
                        </label>
                        <label>الحد الأدنى الخاص (د.ك)
                            <input wire:model="customMinimumOrder" type="number" min="0" step="0.001" required style="display:block;width:100%;padding:10px;margin-top:6px;border:1px solid #d8e3da;border-radius:9px">
                            @error('customMinimumOrder')<small style="color:#b42318">{{ $message }}</small>@enderror
                        </label>
                    @endif
                    <div style="display:flex;gap:8px;align-items:center">
                        <button type="submit" style="border:0;border-radius:9px;padding:10px 16px;background:#1d5b3d;color:white">حفظ إعدادات المنطقة</button>
                        <button type="button" wire:click="$set('selectedAreaId', null)" style="padding:9px 14px;border:1px solid #d8e3da;border-radius:9px;background:white">إغلاق</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</section>
