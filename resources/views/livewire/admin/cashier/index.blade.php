<section>
    @if(session('status'))<div style="padding:12px 15px;border-radius:10px;background:#e5f4e9;color:#205d3b;margin-bottom:14px">{{ session('status') }}</div>@endif
    <div class="stats" style="grid-template-columns:repeat(2,minmax(0,1fr))">
        <div class="stat"><span>طلبات جاهزة أو مكتملة</span><strong>{{ $orders->total() }}</strong></div>
        <div class="stat"><span>طلبات غير مسددة في الصفحة الحالية</span><strong>{{ $orders->getCollection()->where('payment_status','unpaid')->count() }}</strong></div>
    </div>
    <div class="panel">
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px">
            <input wire:model.live.debounce.300ms="search" placeholder="ابحث برقم الطلب أو اسم العميل أو الهاتف" style="flex:1;min-width:220px;padding:11px;border:1px solid #d8e3da;border-radius:9px">
            <select wire:model.live="fulfillmentFilter" style="padding:11px;border:1px solid #d8e3da;border-radius:9px;background:#fff">
                <option value="">كل طرق الاستلام</option><option value="pickup">استلام من المطعم</option><option value="delivery">توصيل</option>
            </select>
        </div>
        <div class="table-wrap">
            <table style="min-width:800px">
                <thead><tr><th>رقم الطلب</th><th>العميل</th><th>طريقة الاستلام</th><th>الأصناف</th><th>الإجمالي</th><th>حالة الدفع</th><th>التحصيل</th></tr></thead>
                <tbody>
                @forelse($orders as $order)
                    <tr wire:key="cashier-order-{{ $order->id }}">
                        <td><strong>{{ $order->order_number }}</strong><div class="muted">{{ $order->created_at?->timezone('Asia/Kuwait')->format('Y-m-d H:i') }}</div></td>
                        <td>{{ $order->customer_name }}<div class="muted">{{ $order->customer_phone }}</div></td>
                        <td>{{ $order->fulfillment_type==='pickup' ? 'استلام من المطعم' : 'توصيل · '.$order->delivery_area_name }}</td>
                        <td>@foreach($order->items as $item)<div>{{ $item->product_name }} × {{ $item->quantity }}</div>@endforeach</td>
                        <td><strong>{{ $order->total }} د.ك</strong></td>
                        <td><span class="status">{{ $order->payment_status==='paid' ? 'مدفوع' : 'غير مدفوع' }}</span></td>
                        <td>
                            @if($order->payment_status!=='paid' && (($order->fulfillment_type==='pickup' && in_array($order->status,['ready','completed'],true)) || ($order->fulfillment_type==='delivery' && in_array($order->status,['out_for_delivery','completed'],true))))
                                <button wire:click="collectCash({{ $order->id }})" wire:confirm="تأكيد استلام المبلغ نقدًا؟" wire:loading.attr="disabled" style="border:0;border-radius:8px;padding:9px 12px;background:#1d5b3d;color:#fff;cursor:pointer;font:inherit;font-size:12px">تحصيل نقدي</button>
                            @elseif($order->payment_status==='paid')
                                <span style="color:#286344;font-size:12px">تم التحصيل ✓</span>
                            @else
                                <span class="muted">بانتظار التسليم</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty">لا توجد طلبات جاهزة للتحصيل</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding-top:16px">{{ $orders->links() }}</div>
    </div>
</section>
