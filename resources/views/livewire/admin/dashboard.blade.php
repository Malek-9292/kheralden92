<section>
    <div class="stats">
        <div class="stat"><span>الطلبات الجديدة</span><strong>{{ number_format($stats['pending_orders']) }}</strong></div>
        <div class="stat"><span>الأصناف النشطة</span><strong>{{ number_format($stats['active_products']) }}</strong></div>
        @if($stats['employees'] !== null)<div class="stat"><span>الموظفون النشطون</span><strong>{{ number_format($stats['employees']) }}</strong></div>@endif
        @if($stats['delivery_areas'] !== null)<div class="stat"><span>مناطق التوصيل النشطة</span><strong>{{ number_format($stats['delivery_areas']) }}</strong></div>@endif
    </div>
    <div class="panel">
        <h2>أحدث الطلبات</h2>
        @if($recentOrders->isEmpty())
            <div class="empty">لا توجد طلبات مسجلة حتى الآن</div>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>رقم الطلب</th><th>العميل</th><th>نوع الاستلام</th><th>الإجمالي</th><th>الحالة</th><th>التاريخ</th></tr></thead>
                    <tbody>
                    @foreach($recentOrders as $order)
                        <tr>
                            <td><a href="/admin/orders/{{ $order->id }}"><strong>{{ $order->order_number }}</strong></a></td>
                            <td>{{ $order->customer_name }}<div class="muted">{{ $order->customer_phone }}</div></td>
                            <td>{{ $order->fulfillment_type === 'pickup' ? 'استلام من المطعم' : 'توصيل' }}</td>
                            <td>{{ $order->total }} د.ك</td>
                            <td><span class="status">{{ $order->status }}</span></td>
                            <td>{{ $order->created_at?->timezone('Asia/Kuwait')->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
