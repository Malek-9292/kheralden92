<section>
    <div class="panel" style="margin-bottom:16px">
        <form wire:submit.prevent="$refresh" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap">
            <label class="field">من تاريخ<input type="date" wire:model="dateFrom"></label>
            <label class="field">إلى تاريخ<input type="date" wire:model="dateTo"></label>
            @if($isSystemAdmin)
                <label class="field">الفرع
                    <select wire:model="branchId"><option value="">كل الفروع</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select>
                </label>
            @endif
            <label class="field">طريقة الاستلام
                <select wire:model="fulfillmentFilter"><option value="">الكل</option><option value="pickup">استلام</option><option value="delivery">توصيل</option></select>
            </label>
            <button type="submit" class="primary">تطبيق الفلتر</button>
        </form>
        @error('dateFrom')<small style="color:#b42318">{{ $message }}</small>@enderror
        @error('dateTo')<small style="color:#b42318">{{ $message }}</small>@enderror
    </div>
    <div class="stats">
        <div class="stat"><span>إجمالي الطلبات</span><strong>{{ number_format($totalOrders) }}</strong></div>
        <div class="stat"><span>الطلبات المسددة</span><strong>{{ number_format($paidCount) }}</strong></div>
        <div class="stat"><span>المبيعات المحصلة</span><strong>{{ number_format((float)$paidRevenue,3) }} د.ك</strong></div>
        <div class="stat"><span>متوسط الفاتورة المسددة</span><strong>{{ number_format($averageTicket,3) }} د.ك</strong></div>
    </div>
    <div class="grid" style="grid-template-columns:repeat(2,minmax(0,1fr));gap:16px">
        <div class="panel">
            <h2>حالات الطلبات</h2>
            @php($labels=['pending'=>'جديد','confirmed'=>'مؤكد','preparing'=>'قيد التجهيز','ready'=>'جاهز','out_for_delivery'=>'خرج للتوصيل','completed'=>'مكتمل','cancelled'=>'ملغي'])
            @forelse($statusCounts as $status => $count)
                <div class="line"><span>{{ $labels[$status] ?? $status }}</span><strong>{{ number_format($count) }}</strong></div>
            @empty<div class="empty">لا توجد طلبات في الفترة المحددة</div>@endforelse
            <div class="line"><span>الطلبات الملغاة</span><strong>{{ number_format($cancelledOrders) }}</strong></div>
        </div>
        <div class="panel">
            <h2>المبيعات اليومية المحصلة</h2>
            @forelse($dailySales as $day)
                <div class="line"><span>{{ \Carbon\Carbon::parse($day->day)->format('Y-m-d') }}</span><strong>{{ number_format((float)$day->total,3) }} د.ك</strong></div>
            @empty<div class="empty">لا توجد مبالغ محصلة في الفترة المحددة</div>@endforelse
        </div>
    </div>
    <div class="panel" style="margin-top:16px">
        <h2>الأصناف الأكثر طلبًا خلال الفترة</h2>
        <div class="table-wrap"><table>
            <thead><tr><th>الصنف</th><th>القسم</th><th>الكمية المباعة</th><th>قيمة الأصناف</th></tr></thead>
            <tbody>
            @forelse($topProducts as $product)
                <tr><td><strong>{{ $product->product_name }}</strong></td><td>{{ $product->category_name }}</td><td>{{ number_format($product->quantity) }}</td><td>{{ number_format((float)$product->sales,3) }} د.ك</td></tr>
            @empty<tr><td colspan="4"><div class="empty">لا توجد مبيعات أصناف في الفترة المحددة</div></td></tr>@endforelse
            </tbody>
        </table></div>
    </div>
</section>
