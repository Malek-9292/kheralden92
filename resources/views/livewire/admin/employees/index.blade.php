<section>
    @if(session('status'))<div style="padding:12px 15px;border-radius:10px;background:#e5f4e9;color:#205d3b;margin-bottom:14px">{{ session('status') }}</div>@endif
    <div class="panel" style="margin-bottom:18px">
        <h2>{{ $editingId ? 'تعديل حساب موظف' : 'إضافة موظف جديد' }}</h2>
        <form wire:submit="save" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px">
            @if($isSystemAdmin)
                <label class="field">الفرع
                    <select wire:model="branchId" required><option value="">اختر الفرع</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select>
                    @error('branchId')<small style="color:#b42318">{{ $message }}</small>@enderror
                </label>
            @endif
            <label class="field">اسم الموظف<input wire:model="name" required maxlength="150">@error('name')<small style="color:#b42318">{{ $message }}</small>@enderror</label>
            <label class="field">رقم الهاتف<input wire:model="phone" required maxlength="30" type="tel">@error('phone')<small style="color:#b42318">{{ $message }}</small>@enderror</label>
            <label class="field">كلمة المرور {{ $editingId ? '(اتركها فارغة دون تغيير)' : '(12 حرفًا على الأقل)' }}
                <input wire:model="password" type="password" autocomplete="new-password" {{ $editingId ? '' : 'required' }}>
                @error('password')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label class="field">حالة الحساب
                <select wire:model="status"><option value="active">نشط</option><option value="suspended">موقوف مؤقتًا</option><option value="inactive">غير نشط</option></select>
            </label>
            <label class="field">بداية الصلاحية (اختياري)<input wire:model="validFrom" type="datetime-local">@error('validFrom')<small style="color:#b42318">{{ $message }}</small>@enderror</label>
            <label class="field">نهاية الصلاحية (اختياري)<input wire:model="validUntil" type="datetime-local">@error('validUntil')<small style="color:#b42318">{{ $message }}</small>@enderror</label>
            <div class="field full">
                <strong>الأدوار والصلاحيات</strong>
                <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-top:7px">
                    @forelse($roles as $role)
                        <label style="display:flex;gap:8px;align-items:center;padding:9px;border:1px solid #e2eae3;border-radius:9px;font-size:12px">
                            <input type="checkbox" wire:model="roleIds" value="{{ $role->id }}"> {{ $role->name }}
                        </label>
                    @empty
                        <div class="muted">لا توجد أدوار متاحة لهذا الفرع.</div>
                    @endforelse
                </div>
                @error('roleIds')<small style="color:#b42318">{{ $message }}</small>@enderror
                @error('roleIds.*')<small style="color:#b42318">{{ $message }}</small>@enderror
            </div>
            <div style="display:flex;gap:8px;align-items:center">
                <button type="submit" wire:loading.attr="disabled" class="primary">حفظ الموظف</button>
                @if($editingId)<button type="button" wire:click="resetForm" class="secondary">إلغاء التعديل</button>@endif
            </div>
        </form>
    </div>
    <div class="panel">
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px">
            <input wire:model.live.debounce.300ms="search" placeholder="ابحث بالاسم أو رقم الهاتف" style="flex:1;min-width:220px;padding:11px;border:1px solid #d8e3da;border-radius:9px">
            <select wire:model.live="statusFilter" style="padding:11px;border:1px solid #d8e3da;border-radius:9px;background:#fff">
                <option value="">كل الحالات</option><option value="active">نشط</option><option value="suspended">موقوف مؤقتًا</option><option value="inactive">غير نشط</option>
            </select>
        </div>
        <div class="table-wrap"><table>
            <thead><tr><th>الموظف</th><th>الهاتف</th><th>الأدوار</th><th>الحالة</th><th>الصلاحية</th><th>إجراء</th></tr></thead>
            <tbody>
            @forelse($employees as $employee)
                <tr wire:key="employee-{{ $employee->id }}">
                    <td><strong>{{ $employee->name }}</strong></td>
                    <td dir="ltr">{{ $employee->phone }}</td>
                    <td>{{ $employee->roles->pluck('name')->join('، ') }}</td>
                    <td><span class="status">{{ ['active'=>'نشط','suspended'=>'موقوف','inactive'=>'غير نشط'][$employee->status] ?? $employee->status }}</span></td>
                    <td class="muted">{{ $employee->valid_from?->format('Y-m-d') ?? 'من الآن' }} — {{ $employee->valid_until?->format('Y-m-d') ?? 'مفتوحة' }}</td>
                    <td><button wire:click="edit({{ $employee->id }})" class="secondary" style="padding:7px 10px;font-size:12px">تعديل</button></td>
                </tr>
            @empty<tr><td colspan="6"><div class="empty">لا يوجد موظفون مطابقون</div></td></tr>@endforelse
            </tbody>
        </table></div>
        <div style="padding-top:16px">{{ $employees->links() }}</div>
    </div>
</section>
