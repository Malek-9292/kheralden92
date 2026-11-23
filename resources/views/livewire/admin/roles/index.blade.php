<section>
    @if(session('status'))<div class="flash">{{ session('status') }}</div>@endif
    <div class="panel" style="margin-bottom:18px">
        <h2>{{ $editingId ? 'تعديل دور' : 'إنشاء دور جديد' }}</h2>
        <form wire:submit="save" class="form-grid">
            @if($isSystemAdmin)
                <label class="field">الفرع
                    <select wire:model="branchId" required><option value="">اختر الفرع</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select>
                </label>
            @endif
            <label class="field">اسم الدور<input wire:model="name" required maxlength="100" placeholder="مثال: مشرف صالة">@error('name')<small style="color:#b42318">{{ $message }}</small>@enderror</label>
            <label class="field">المعرف البرمجي (slug)<input wire:model="slug" required maxlength="100" placeholder="floor_supervisor" dir="ltr">@error('slug')<small style="color:#b42318">{{ $message }}</small>@enderror</label>
            <div class="field full">
                <strong>حدد الصلاحيات الممنوحة للدور</strong>
                <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:8px">
                    @foreach($permissionsByModule as $module => $permissions)
                        <div style="border:1px solid #e2eae3;border-radius:12px;padding:12px">
                            <strong style="font-size:13px">{{ $module }}</strong>
                            <div style="display:grid;gap:8px;margin-top:10px">
                                @foreach($permissions as $permission)
                                    <label style="display:flex;gap:8px;align-items:center;font-size:12px">
                                        <input type="checkbox" wire:model="permissionSlugs" value="{{ $permission->slug }}">
                                        <span>{{ $permission->name }} <small class="muted" dir="ltr">{{ $permission->slug }}</small></span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('permissionSlugs')<small style="color:#b42318">{{ $message }}</small>@enderror
                @error('permissionSlugs.*')<small style="color:#b42318">{{ $message }}</small>@enderror
            </div>
            <div style="display:flex;gap:8px;align-items:center">
                <button type="submit" wire:loading.attr="disabled" class="primary">حفظ الدور</button>
                @if($editingId)<button type="button" wire:click="resetForm" class="secondary">إلغاء التعديل</button>@endif
            </div>
        </form>
    </div>
    <div class="panel">
        <h2>الأدوار المسجلة</h2>
        <div class="table-wrap"><table>
            <thead><tr><th>الدور</th><th>الفرع</th><th>عدد الصلاحيات</th><th>الصلاحيات</th><th>إجراء</th></tr></thead>
            <tbody>
            @forelse($roles as $role)
                <tr wire:key="role-{{ $role->id }}">
                    <td><strong>{{ $role->name }}</strong><div class="muted" dir="ltr">{{ $role->slug }}</div></td>
                    <td>{{ $role->branch?->name ?? 'عام' }}</td>
                    <td>{{ $role->permissions->count() }}</td>
                    <td style="max-width:360px"><div style="display:flex;gap:5px;flex-wrap:wrap">@foreach($role->permissions as $permission)<span class="status">{{ $permission->name }}</span>@endforeach</div></td>
                    <td>
                        @if(!$role->is_system && $role->branch_id !== null)
                            <button wire:click="edit({{ $role->id }})" class="secondary" style="padding:7px 10px;font-size:12px">تعديل</button>
                        @else
                            <span class="muted">للعرض فقط</span>
                        @endif
                    </td>
                </tr>
            @empty<tr><td colspan="5"><div class="empty">لا توجد أدوار</div></td></tr>@endforelse
            </tbody>
        </table></div>
    </div>
</section>
