<section>
    @if(session('status'))<div style="padding:12px 15px;border-radius:10px;background:#e5f4e9;color:#205d3b;margin-bottom:14px">{{ session('status') }}</div>@endif
    <div class="panel" style="margin-bottom:18px">
        <h2>{{ $editingId ? 'تعديل صنف' : 'إضافة صنف جديد' }}</h2>
        <form wire:submit="save" class="form-grid">
            @if($isSystemAdmin)
                <label style="font-size:13px">الفرع
                    <select wire:model="branchId" required style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px;background:#fff">
                        @foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach
                    </select>
                </label>
            @endif
            <label style="font-size:13px">القسم
                <select wire:model="categoryId" required style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px;background:#fff">
                    <option value="">اختر القسم</option>
                    @foreach($formCategories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
                </select>
                @error('categoryId')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label style="font-size:13px">اسم الصنف
                <input wire:model="name" required maxlength="180" style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px">
                @error('name')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label style="font-size:13px">السعر (د.ك)
                <input wire:model="price" type="number" min="0.001" step="0.001" required style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px">
                @error('price')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label style="font-size:13px">السعر قبل الخصم (اختياري)
                <input wire:model="compareAtPrice" type="number" min="0.001" step="0.001" style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px">
            </label>
            <label style="font-size:13px">ترتيب الظهور
                <input wire:model="sortOrder" type="number" min="0" style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px">
            </label>
            <label style="font-size:13px">نوع الخبز
                <select wire:model="breadType" style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px;background:#fff">
                    <option value="">حسب القسم (صمون للساندويتشات / صاج للصاج)</option>
                    <option value="samoon">خبز صمون</option>
                    <option value="saj">خبز صاج</option>
                    <option value="none">بدون خبز محدد</option>
                </select>
                @error('breadType')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <label style="font-size:13px;grid-column:1/-1">الوصف (يظهر في القائمة والتفاصيل)
                <textarea wire:model="description" rows="2" maxlength="5000" style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px"></textarea>
            </label>
            <label style="font-size:13px;grid-column:1/-1">المكونات (لتفاصيل الصنف فقط — مثل صواني المشويات)
                <textarea wire:model="ingredients" rows="3" maxlength="5000" placeholder="تظهر داخل تفاصيل الصنف فقط ولا تظهر في شبكة المنيو" style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px"></textarea>
                @error('ingredients')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            <div style="grid-column:1/-1;border:1px solid #e5ece6;border-radius:12px;padding:12px;background:#f8fbf8">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px">
                    <strong style="font-size:13px">فترات الظهور اليومية (اختياري)</strong>
                    <button type="button" wire:click="addAvailabilityWindow" class="secondary">إضافة فترة</button>
                </div>
                <p class="muted" style="margin:0 0 10px">بدون فترات يظهر الصنف طوال اليوم. الأوقات بتوقيت الكويت.</p>
                @forelse($availabilityWindows as $index => $window)
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:8px;align-items:end;margin-bottom:8px">
                        <label style="font-size:12px">من
                            <input type="time" wire:model="availabilityWindows.{{ $index }}.start_time" style="display:block;width:100%;padding:9px;border:1px solid #d8e3da;border-radius:9px;margin-top:4px">
                        </label>
                        <label style="font-size:12px">إلى
                            <input type="time" wire:model="availabilityWindows.{{ $index }}.end_time" style="display:block;width:100%;padding:9px;border:1px solid #d8e3da;border-radius:9px;margin-top:4px">
                        </label>
                        <label style="font-size:12px;display:flex;align-items:center;gap:6px;padding-bottom:10px"><input type="checkbox" wire:model="availabilityWindows.{{ $index }}.is_active"> نشطة</label>
                        <button type="button" wire:click="removeAvailabilityWindow({{ $index }})" class="secondary" style="margin-bottom:8px;color:#b42318;border-color:#f0c7c3">حذف</button>
                    </div>
                    @error('availabilityWindows.'.$index.'.end_time')<small style="color:#b42318">{{ $message }}</small>@enderror
                @empty
                    <div class="muted" style="font-size:12px">لا توجد فترات مخصصة.</div>
                @endforelse
            </div>
            <label style="font-size:13px;grid-column:1/-1">صور الصنف (JPG / WEBP، حتى 3 ميجابايت للصورة)
                <input wire:model="images" type="file" accept=".jpg,.jpeg,.webp,image/jpeg,image/webp" multiple style="display:block;width:100%;padding:10px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px">
                @error('images')<small style="color:#b42318">{{ $message }}</small>@enderror
                @error('images.*')<small style="color:#b42318">{{ $message }}</small>@enderror
            </label>
            @if(count($images))
                <div style="grid-column:1/-1;display:flex;gap:10px;flex-wrap:wrap">
                    @foreach($images as $index => $image)
                        <label style="border:1px solid #d8e3da;border-radius:10px;padding:8px;font-size:12px">
                            <img src="{{ $image->temporaryUrl() }}" alt="معاينة صورة" style="width:90px;height:75px;object-fit:cover;border-radius:7px;display:block;margin-bottom:6px">
                            <input type="radio" wire:model="primaryImageIndex" value="{{ $index }}"> صورة أساسية
                        </label>
                    @endforeach
                </div>
            @endif
            @if($editingId && count($images)===0)
                <div style="grid-column:1/-1;color:#6c8175;font-size:12px">اترك الصور فارغة للاحتفاظ بالصور الحالية، أو اختر صورًا جديدة لإضافتها وتحديد الصورة الأساسية.</div>
            @endif
            @if($editingId)
                <label style="font-size:13px;grid-column:1/-1">سبب تعديل السعر (إجباري عند تغيير السعر)
                    <input wire:model="reason" maxlength="500" style="display:block;width:100%;padding:11px;border:1px solid #d8e3da;border-radius:9px;margin-top:6px">
                    @error('reason')<small style="color:#b42318">{{ $message }}</small>@enderror
                </label>
            @endif
            <label style="display:flex;align-items:center;gap:9px;font-size:13px"><input wire:model="isActive" type="checkbox"> الصنف متاح للعملاء</label>
            <label style="display:flex;align-items:center;gap:9px;font-size:13px"><input wire:model="isBestSeller" type="checkbox"> الأكثر طلبًا</label>
            <div style="display:flex;gap:8px;align-items:center">
                <button type="submit" wire:loading.attr="disabled" style="border:0;border-radius:9px;padding:11px 18px;background:#1d5b3d;color:#fff;font:inherit;cursor:pointer">حفظ الصنف</button>
                @if($editingId)<button type="button" wire:click="resetForm" style="border:1px solid #d8e3da;border-radius:9px;padding:10px 15px;background:#fff;font:inherit;cursor:pointer">إلغاء التعديل</button>@endif
            </div>
            <div wire:loading wire:target="images" style="font-size:12px;color:#60786a">جارٍ رفع الصور...</div>
        </form>
        @if($editingId)
            @if($editingProduct)
                <div style="margin-top:18px;border-top:1px solid #edf1ed;padding-top:14px">
                    <strong style="font-size:13px">الصور الحالية</strong>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px">
                        @foreach($editingProduct->images as $image)
                            <div style="border:1px solid #e2e9e3;border-radius:10px;padding:7px">
                                <img src="{{ $image->url }}" alt="{{ $editingProduct->name }}" style="width:100px;height:80px;object-fit:cover;border-radius:7px;display:block">
                                <div style="font-size:11px;margin-top:5px">{{ $image->is_primary ? 'الصورة الأساسية' : 'صورة إضافية' }}</div>
                                @if($editingProduct->images->count()>1)
                                    <button type="button" wire:click="deleteImage({{ $image->id }})" wire:confirm="حذف هذه الصورة؟" style="border:0;background:#fcebea;color:#a33a31;border-radius:7px;padding:5px 8px;margin-top:5px;font-size:11px">حذف الصورة</button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
    <div class="panel">
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px">
            <input wire:model.live.debounce.300ms="search" placeholder="ابحث باسم الصنف..." style="flex:1;min-width:220px;padding:11px;border:1px solid #d8e3da;border-radius:9px">
            <select wire:model.live="categoryFilter" style="padding:11px;border:1px solid #d8e3da;border-radius:9px;background:#fff">
                <option value="">كل الأقسام</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
            </select>
            <select wire:model.live="statusFilter" style="padding:11px;border:1px solid #d8e3da;border-radius:9px;background:#fff">
                <option value="">كل الحالات</option><option value="active">متاح</option><option value="inactive">متوقف</option>
            </select>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>الصورة</th><th>الصنف</th><th>القسم</th><th>السعر</th><th>الحالة</th><th>الأكثر طلبًا</th><th>إجراءات</th></tr></thead>
                <tbody>
                @forelse($products as $product)
                    <tr wire:key="product-{{ $product->id }}">
                        <td data-label="الصورة">@if($product->primaryImage)<img src="{{ $product->primaryImage->url }}" alt="{{ $product->name }}" style="width:72px;height:54px;object-fit:contain;background:#edf3ed;border-radius:8px">@else—@endif</td>
                        <td data-label="الصنف"><strong>{{ $product->name }}</strong><div class="muted">{{ \Illuminate\Support\Str::limit($product->description,55) }}</div></td>
                        <td data-label="القسم">{{ $product->category?->name }}</td>
                        <td data-label="السعر">{{ $product->price }} د.ك</td>
                        <td data-label="الحالة"><span class="status">{{ $product->is_active ? 'متاح' : 'متوقف' }}</span></td>
                        <td data-label="الأكثر طلبًا">{{ $product->is_best_seller ? 'نعم' : 'لا' }}</td>
                        <td data-label="إجراءات"><div style="display:flex;gap:6px;flex-wrap:wrap">
                            <button wire:click="edit({{ $product->id }})" style="border:0;border-radius:8px;padding:10px 12px;min-height:40px;background:#edf3f8;color:#315b83;cursor:pointer;font:inherit;font-size:12px">تعديل</button>
                            <button wire:click="toggleActive({{ $product->id }})" style="border:0;border-radius:8px;padding:10px 12px;min-height:40px;background:#f0f4ef;color:#42664c;cursor:pointer;font:inherit;font-size:12px">{{ $product->is_active ? 'إيقاف' : 'تفعيل' }}</button>
                            <button wire:click="toggleBestSeller({{ $product->id }})" style="border:0;border-radius:8px;padding:10px 12px;min-height:40px;background:#fff5df;color:#805d16;cursor:pointer;font:inherit;font-size:12px">{{ $product->is_best_seller ? 'إزالة الأكثر طلبًا' : 'الأكثر طلبًا' }}</button>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty">لا توجد أصناف مطابقة</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding-top:16px">{{ $products->links() }}</div>
    </div>
</section>
