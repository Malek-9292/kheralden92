<section>
    @if(session('status'))<div class="flash">{{ session('status') }}</div>@endif
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="ابحث برقم الطلب أو اسم العميل أو الهاتف" style="flex:1;min-width:240px;padding:12px;border:1px solid #d8e3da;border-radius:10px;font:inherit">
        <select wire:model.live="statusFilter" style="padding:12px;border:1px solid #d8e3da;border-radius:10px;background:#fff;font:inherit">
            <option value="">كل الحالات</option>
            <option value="pending">جديد</option><option value="confirmed">مؤكد</option><option value="preparing">قيد التجهيز</option>
            <option value="ready">جاهز</option><option value="out_for_delivery">خرج للتوصيل</option><option value="completed">مكتمل</option><option value="cancelled">ملغي</option>
        </select>
        <select wire:model.live="fulfillmentFilter" style="padding:12px;border:1px solid #d8e3da;border-radius:10px;background:#fff;font:inherit">
            <option value="">كل طرق الاستلام</option><option value="pickup">استلام من المطعم</option><option value="delivery">توصيل</option>
        </select>
    </div>
    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead><tr><th>رقم الطلب</th><th>العميل</th><th>الأصناف</th><th>الاستلام والعنوان</th><th>الإجمالي</th><th>الحالة / الدفع</th><th>الإجراءات</th></tr></thead>
                <tbody>
                @forelse($orders as $order)
                    <tr wire:key="order-{{ $order->id }}">
                        <td data-label="رقم الطلب"><strong>{{ $order->order_number }}</strong><div class="muted">{{ $order->created_at?->timezone('Asia/Kuwait')->format('Y-m-d H:i') }}</div></td>
                        <td data-label="العميل">
                            {{ $order->customer_name }}
                            <div class="muted">{{ $order->customer_phone }}</div>
                            @if(filled($order->customer_notes))
                                <div style="margin-top:6px;padding:8px 10px;border-radius:8px;background:#f7faf7;border:1px solid #e5ece6;font-size:12px;line-height:1.6;overflow-wrap:anywhere">
                                    <strong>ملاحظات:</strong> {{ $order->customer_notes }}
                                </div>
                            @endif
                        </td>
                        <td data-label="الأصناف">
                            @foreach($order->items as $item)
                                <div style="margin-bottom:4px">{{ $item->product_name }} × {{ $item->quantity }}</div>
                            @endforeach
                        </td>
                        <td data-label="الاستلام والعنوان">
                            @if($order->fulfillment_type === 'pickup')
                                استلام من المطعم
                            @else
                                توصيل · {{ $order->delivery_area_name }}<div class="muted">قطعة {{ $order->block }}، شارع {{ $order->street }}@if($order->avenue)، جادة {{ $order->avenue }}@endif، منزل {{ $order->house_number }}@if($order->floor)، دور {{ $order->floor }}@endif @if($order->apartment)، شقة {{ $order->apartment }}@endif</div>
                            @endif
                        </td>
                        <td data-label="الإجمالي"><strong>{{ $order->total }} د.ك</strong><div class="muted">رسوم التوصيل: {{ $order->delivery_fee }}</div></td>
                        <td data-label="الحالة / الدفع"><span class="status">{{ ['pending'=>'جديد','confirmed'=>'مؤكد','preparing'=>'قيد التجهيز','ready'=>'جاهز','out_for_delivery'=>'خرج للتوصيل','completed'=>'مكتمل','cancelled'=>'ملغي'][$order->status] ?? $order->status }}</span><div class="muted" style="margin-top:5px">{{ $order->payment_status === 'paid' ? 'تم السداد' : 'غير مدفوع' }}</div></td>
                        <td data-label="الإجراءات">
                            <div style="display:flex;gap:6px;flex-wrap:wrap">
                                @if($canAdvance && !in_array($order->status,['completed','cancelled'],true))
                                    <button wire:click="advance({{ $order->id }})" wire:loading.attr="disabled" style="border:0;border-radius:8px;padding:10px 12px;min-height:40px;background:#e4f2e8;color:#1b603b;cursor:pointer;font:inherit;font-size:12px">الخطوة التالية</button>
                                @endif
                                @if($canCollect && $order->payment_status !== 'paid' && (($order->fulfillment_type === 'pickup' && in_array($order->status,['ready','completed'],true)) || ($order->fulfillment_type === 'delivery' && in_array($order->status,['out_for_delivery','completed'],true))))
                                    <button wire:click="collectCash({{ $order->id }})" wire:loading.attr="disabled" style="border:0;border-radius:8px;padding:10px 12px;min-height:40px;background:#eaf0fb;color:#315b9a;cursor:pointer;font:inherit;font-size:12px">تحصيل نقدي</button>
                                @endif
                                @if($canCancel && !in_array($order->status,['completed','cancelled'],true))
                                    <button wire:click="openCancelModal({{ $order->id }})" wire:loading.attr="disabled" style="border:0;border-radius:8px;padding:10px 12px;min-height:40px;background:#fcebea;color:#a33a31;cursor:pointer;font:inherit;font-size:12px">إلغاء</button>
                                @endif
                                @if($canReprint && $order->status !== 'cancelled')
                                    <button wire:click="reprintKitchen({{ $order->id }})" wire:confirm="إعادة طباعة تذكرة المطبخ؟" wire:loading.attr="disabled" class="secondary" style="padding:10px 12px;min-height:40px;font-size:12px">إعادة تذكرة مطبخ</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty">لا توجد طلبات مطابقة للبحث</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding-top:16px">{{ $orders->links() }}</div>
    </div>

    @if($cancelOrderId)
        <div class="backdrop" style="display:block" wire:click="closeCancelModal" aria-hidden="true"></div>
        <div role="dialog" aria-modal="true" aria-labelledby="cancel-order-title" style="position:fixed;inset:0;z-index:60;display:grid;place-items:center;padding:16px">
            <div class="panel" style="width:min(440px,100%);margin:0;box-shadow:0 18px 50px #12291d33" @click.stop>
                <h2 id="cancel-order-title" style="margin-top:0">سبب إلغاء الطلب</h2>
                <p class="muted" style="margin:0 0 12px;line-height:1.7">اكتب سببًا واضحًا (3 أحرف على الأقل). يُحفظ السبب في سجل التدقيق.</p>
                <label class="field" for="cancelReason">سبب الإلغاء
                    <textarea id="cancelReason" wire:model="cancelReason" rows="3" maxlength="500" required placeholder="مثال: طلب مكرر، العميل طلب الإلغاء، نفاد صنف..."></textarea>
                </label>
                @error('cancelReason')<div class="flash error" style="margin-top:10px">{{ $message }}</div>@enderror
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px">
                    <button type="button" class="primary" wire:click="confirmCancel" wire:loading.attr="disabled" style="background:#a33a31">تأكيد الإلغاء</button>
                    <button type="button" class="secondary" wire:click="closeCancelModal">رجوع</button>
                </div>
            </div>
        </div>
    @endif
</section>
