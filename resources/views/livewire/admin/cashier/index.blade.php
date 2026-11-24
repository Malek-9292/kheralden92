<section wire:poll.5s="pollPending">
    @if(session('status'))<div style="padding:12px 15px;border-radius:10px;background:#e5f4e9;color:#205d3b;margin-bottom:14px">{{ session('status') }}</div>@endif

    <div class="panel" style="margin-bottom:16px;border-color:{{ $pendingCount > 0 ? '#f0c9a0' : '#e5ece6' }};background:{{ $pendingCount > 0 ? '#fff8f0' : '#fff' }}">
        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:space-between">
            <div>
                <strong style="font-size:16px">طلبات جديدة بانتظار القبول</strong>
                <div class="muted" style="margin-top:4px">يُحدَّث طابور الكاشير تلقائيًا كل 5 ثوانٍ. طلبات الكاش من المتجر تظهر مباشرة كـ«مؤكد» مع طباعة المطبخ. الصوت تنبيه فقط ولا يمنع استقبال الطلبات.</div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <span class="status {{ $pendingCount > 0 ? 'warn' : '' }}" style="font-size:14px">{{ $pendingCount }} طلب جديد</span>
                <button type="button" wire:click="toggleMute" class="secondary" style="padding:8px 12px;min-height:40px;font-size:12px">
                    {{ $soundMuted ? 'تفعيل الصوت' : 'كتم الصوت' }}
                </button>
                <a href="/admin/orders?statusFilter=pending" class="secondary" style="padding:8px 12px;min-height:40px;font-size:12px;display:inline-flex;align-items:center;text-decoration:none">فتح الطلبات</a>
            </div>
        </div>

        @if($pendingOrders->isNotEmpty())
            <div class="table-wrap" style="margin-top:14px">
                <table>
                    <thead><tr><th>رقم الطلب</th><th>العميل</th><th>النوع</th><th>الإجمالي</th><th>إجراء</th></tr></thead>
                    <tbody>
                    @foreach($pendingOrders as $pending)
                        <tr wire:key="cashier-pending-{{ $pending->id }}">
                            <td data-label="رقم الطلب"><strong>{{ $pending->order_number }}</strong><div class="muted">{{ $pending->created_at?->timezone('Asia/Kuwait')->format('H:i') }}</div></td>
                            <td data-label="العميل">{{ $pending->customer_name }}<div class="muted">{{ $pending->customer_phone }}</div></td>
                            <td data-label="النوع">{{ $pending->fulfillment_type === 'pickup' ? 'استلام' : 'توصيل' }}</td>
                            <td data-label="الإجمالي"><strong>{{ $pending->total }} د.ك</strong></td>
                            <td data-label="إجراء">
                                @if($canAccept)
                                    <button wire:click="acceptPending({{ $pending->id }})" wire:loading.attr="disabled" style="border:0;border-radius:8px;padding:9px 12px;background:#1d5b3d;color:#fff;cursor:pointer;font:inherit;font-size:12px;min-height:40px">قبول الطلب</button>
                                @else
                                    <span class="muted">راجع شاشة الطلبات</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty" style="padding:18px 8px">لا توجد طلبات جديدة الآن.</div>
        @endif
    </div>

    <div class="stats" style="grid-template-columns:repeat(2,minmax(0,1fr))">
        <div class="stat"><span>طلبات الطابور</span><strong>{{ $orders->total() }}</strong></div>
        <div class="stat"><span>غير مسددة في الصفحة الحالية</span><strong>{{ $orders->getCollection()->where('payment_status','unpaid')->count() }}</strong></div>
    </div>
    <div class="panel">
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px">
            <input wire:model.live.debounce.300ms="search" placeholder="ابحث برقم الطلب أو اسم العميل أو الهاتف" style="flex:1;min-width:220px;padding:11px;border:1px solid #d8e3da;border-radius:9px">
            <select wire:model.live="fulfillmentFilter" style="padding:11px;border:1px solid #d8e3da;border-radius:9px;background:#fff">
                <option value="">كل طرق الاستلام</option><option value="pickup">استلام من المطعم</option><option value="delivery">توصيل</option>
            </select>
            <select wire:model.live="statusFilter" style="padding:11px;border:1px solid #d8e3da;border-radius:9px;background:#fff">
                <option value="">كل الحالات النشطة</option>
                <option value="pending">جديد</option>
                <option value="confirmed">مؤكد</option>
                <option value="preparing">قيد التجهيز</option>
                <option value="ready">جاهز</option>
                <option value="out_for_delivery">خرج للتوصيل</option>
                <option value="completed">مكتمل</option>
            </select>
            <select wire:model.live="paymentFilter" style="padding:11px;border:1px solid #d8e3da;border-radius:9px;background:#fff">
                <option value="unpaid">غير مسدد</option><option value="paid">مسدد</option><option value="">الكل</option>
            </select>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>رقم الطلب</th><th>العميل</th><th>الحالة</th><th>النوع</th><th>الإجمالي</th><th>الدفع</th><th>إجراء</th></tr></thead>
                <tbody>
                @forelse($orders as $order)
                    <tr wire:key="cashier-order-{{ $order->id }}">
                        <td data-label="رقم الطلب"><strong>{{ $order->order_number }}</strong><div class="muted">{{ $order->created_at?->timezone('Asia/Kuwait')->format('Y-m-d H:i') }}</div></td>
                        <td data-label="العميل">{{ $order->customer_name }}<div class="muted">{{ $order->customer_phone }}</div></td>
                        <td data-label="الحالة"><span class="status {{ in_array($order->status, ['pending', 'ready'], true) ? 'warn' : '' }}">{{ $statusLabels[$order->status] ?? $order->status }}</span></td>
                        <td data-label="النوع">{{ $order->fulfillment_type==='pickup' ? 'استلام' : 'توصيل' }}@if($order->fulfillment_type==='delivery' && $order->delivery_area_name)<div class="muted">{{ $order->delivery_area_name }}</div>@endif</td>
                        <td data-label="الإجمالي"><strong>{{ $order->total }} د.ك</strong></td>
                        <td data-label="الدفع"><span class="status">{{ $order->payment_status==='paid' ? 'مدفوع' : 'غير مدفوع' }}</span></td>
                        <td data-label="إجراء">
                            @if($order->status === 'pending')
                                @if($canAccept)
                                    <button wire:click="acceptPending({{ $order->id }})" wire:loading.attr="disabled" style="border:0;border-radius:8px;padding:9px 12px;background:#1d5b3d;color:#fff;cursor:pointer;font:inherit;font-size:12px;min-height:40px">قبول الطلب</button>
                                @else
                                    <span class="muted">بانتظار القبول</span>
                                @endif
                            @elseif($order->payment_status!=='paid' && (($order->fulfillment_type==='pickup' && in_array($order->status,['ready','completed'],true)) || ($order->fulfillment_type==='delivery' && in_array($order->status,['out_for_delivery','completed'],true))))
                                <button wire:click="collectCash({{ $order->id }})" wire:confirm="تأكيد استلام المبلغ نقدًا؟" wire:loading.attr="disabled" style="border:0;border-radius:8px;padding:9px 12px;background:#1d5b3d;color:#fff;cursor:pointer;font:inherit;font-size:12px">تحصيل نقدي</button>
                            @elseif($order->payment_status==='paid')
                                <span style="color:#286344;font-size:12px">تم التحصيل ✓</span>
                            @else
                                <span class="muted">قيد التنفيذ</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty">لا توجد طلبات في طابور الكاشير</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding-top:16px">{{ $orders->links() }}</div>
    </div>
</section>

@script
<script>
    const playBeep = () => {
        try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            const ctx = new Ctx();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = 880;
            gain.gain.value = 0.05;
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            setTimeout(() => { osc.stop(); ctx.close(); }, 220);
        } catch (e) {
            // Never block order handling if audio fails.
        }
    };
    $wire.on('cashier-new-order-beep', () => playBeep());
</script>
@endscript
