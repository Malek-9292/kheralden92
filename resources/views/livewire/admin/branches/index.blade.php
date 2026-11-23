<section>
    @if(session('status'))<div style="padding:12px 15px;border-radius:10px;background:#e5f4e9;color:#205d3b;margin-bottom:14px">{{ session('status') }}</div>@endif
    <div class="panel" style="margin-bottom:18px">
        <h2>{{ $editingId ? 'تعديل بيانات الفرع' : 'إضافة فرع جديد' }}</h2>
        <form wire:submit="save" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px">
            <label style="font-size:13px">كود الفرع
                <input wire:model="code" required maxlength="30" placeholder="KHAL" style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px;direction:ltr">
                @error('code')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label style="font-size:13px">اسم الفرع
                <input wire:model="name" required maxlength="150" placeholder="فتوش الخالدية" style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px">
                @error('name')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label style="font-size:13px">المنطقة الزمنية
                <input wire:model="timezone" required placeholder="Asia/Kuwait" style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px;direction:ltr">
                @error('timezone')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label style="display:flex;align-items:center;gap:9px;font-size:13px"><input wire:model="isActive" type="checkbox"> الفرع نشط</label>
            <div style="display:flex;gap:8px;align-items:center">
                <button type="submit" wire:loading.attr="disabled" class="primary">حفظ الفرع</button>
                @if($editingId)<button type="button" wire:click="resetForm" class="secondary">إلغاء</button>@endif
            </div>
        </form>
    </div>
    <div class="panel">
        <input wire:model.live.debounce.300ms="search" placeholder="ابحث باسم الفرع أو الكود" style="width:100%;max-width:420px;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-bottom:14px">
        <div class="table-wrap"><table>
            <thead><tr><th>الكود</th><th>اسم الفرع</th><th>المنطقة الزمنية</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
            <tbody>
            @forelse($branches as $branch)
                <tr wire:key="branch-{{ $branch->id }}">
                    <td dir="ltr">{{ $branch->code }}</td><td><strong>{{ $branch->name }}</strong></td><td dir="ltr">{{ $branch->timezone }}</td>
                    <td><span class="status">{{ $branch->is_active ? 'نشط' : 'متوقف' }}</span></td>
                    <td><div style="display:flex;gap:6px">
                        <button wire:click="edit({{ $branch->id }})" class="secondary" style="padding:7px 10px;font-size:12px">تعديل</button>
                        <button wire:click="toggleActive({{ $branch->id }})" wire:confirm="تأكيد تغيير حالة الفرع؟" class="secondary" style="padding:7px 10px;font-size:12px">{{ $branch->is_active ? 'إيقاف' : 'تفعيل' }}</button>
                    </div></td>
                </tr>
            @empty<tr><td colspan="5"><div class="empty">لا توجد فروع</div></td></tr>@endforelse
            </tbody>
        </table></div>
        <div style="padding-top:16px">{{ $branches->links() }}</div>
    </div>
</section>
