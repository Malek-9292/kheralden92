<section>
    @if(session('status'))<div style="padding:12px 15px;border-radius:10px;background:#e5f4e9;color:#205d3b;margin-bottom:14px">{{ session('status') }}</div>@endif
    <div class="panel" style="margin-bottom:18px">
        <h2>{{ $editingId ? 'تعديل قسم' : 'إضافة قسم جديد' }}</h2>
        <form wire:submit="save" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px">
            @if($isSystemAdmin)
                <label style="font-size:13px">الفرع
                    <select wire:model="branchId" required style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px;background:#fff">
                        @foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach
                    </select>
                </label>
            @endif
            <label style="font-size:13px">اسم القسم
                <input wire:model="name" maxlength="150" required style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px">
                @error('name')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label style="font-size:13px">القسم الرئيسي (اختياري)
                <select wire:model="parentId" style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px;background:#fff">
                    <option value="">قسم رئيسي</option>
                    @foreach($parents as $parent)<option value="{{ $parent->id }}">{{ $parent->name }}</option>@endforeach
                </select>
            </label>
            <label style="font-size:13px">ترتيب الظهور
                <input wire:model="sortOrder" type="number" min="0" style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px">
            </label>
            <label style="display:flex;align-items:center;gap:9px;font-size:13px"><input wire:model="isActive" type="checkbox"> القسم ظاهر للعملاء</label>
            <div style="display:flex;gap:8px;align-items:center">
                <button type="submit" wire:loading.attr="disabled" style="border:0;border-radius:9px;padding:11px 18px;background:#1d5b3d;color:#fff;font:inherit;cursor:pointer">حفظ القسم</button>
                @if($editingId)<button type="button" wire:click="resetForm" style="border:1px solid #d8e3da;border-radius:9px;padding:10px 15px;background:#fff;font:inherit;cursor:pointer">إلغاء التعديل</button>@endif
            </div>
        </form>
    </div>
    <div class="panel">
        <input wire:model.live.debounce.300ms="search" placeholder="ابحث عن قسم..." style="width:100%;max-width:420px;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-bottom:14px">
        <div class="table-wrap">
            <table>
                <thead><tr><th>القسم</th><th>القسم الرئيسي</th><th>عدد الأصناف</th><th>الحالة</th><th>الترتيب</th><th>إجراءات</th></tr></thead>
                <tbody>
                @forelse($categories as $category)
                    <tr wire:key="category-{{ $category->id }}">
                        <td><strong>{{ $category->name }}</strong></td>
                        <td>{{ $category->parent?->name ?? 'قسم رئيسي' }}</td>
                        <td>{{ $category->products_count }}</td>
                        <td><span class="status">{{ $category->is_active ? 'ظاهر' : 'مخفي' }}</span></td>
                        <td>{{ $category->sort_order }}</td>
                        <td><div style="display:flex;gap:6px;flex-wrap:wrap">
                            <button wire:click="edit({{ $category->id }})" style="border:0;border-radius:8px;padding:7px 10px;background:#edf3f8;color:#315b83;cursor:pointer;font:inherit;font-size:12px">تعديل</button>
                            <button wire:click="toggleActive({{ $category->id }})" style="border:0;border-radius:8px;padding:7px 10px;background:#f0f4ef;color:#42664c;cursor:pointer;font:inherit;font-size:12px">{{ $category->is_active ? 'إخفاء' : 'إظهار' }}</button>
                            @if($category->products_count===0)
                                <button wire:click="delete({{ $category->id }})" wire:confirm="تأكيد حذف القسم؟" style="border:0;border-radius:8px;padding:7px 10px;background:#fcebea;color:#a33a31;cursor:pointer;font:inherit;font-size:12px">حذف</button>
                            @endif
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty">لا توجد أقسام حتى الآن</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding-top:16px">{{ $categories->links() }}</div>
    </div>
</section>
