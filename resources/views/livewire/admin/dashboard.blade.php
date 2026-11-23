<section>
    <div class="stats">
        @if($stats['pending_orders'] !== null)
            <div class="stat"><span>الطلبات الجديدة</span><strong>{{ number_format($stats['pending_orders']) }}</strong></div>
        @endif
        @if($stats['today_orders'] !== null)
            <div class="stat"><span>طلبات اليوم</span><strong>{{ number_format($stats['today_orders']) }}</strong></div>
        @endif
        @if($stats['active_products'] !== null)
            <div class="stat"><span>الأصناف النشطة</span><strong>{{ number_format($stats['active_products']) }}</strong></div>
        @endif
        @if($stats['employees'] !== null)
            <div class="stat"><span>الموظفون النشطون</span><strong>{{ number_format($stats['employees']) }}</strong></div>
        @endif
        @if($stats['delivery_areas'] !== null)
            <div class="stat"><span>مناطق التوصيل النشطة</span><strong>{{ number_format($stats['delivery_areas']) }}</strong></div>
        @endif
    </div>

    @if($quickLinks->isNotEmpty())
        <div class="quick-nav">
            @foreach($quickLinks as $link)
                <a href="{{ $link['url'] }}"><strong>{{ $link['label'] }}</strong><span>{{ $link['hint'] }}</span></a>
            @endforeach
        </div>
    @endif

    <div class="panel">
        <h2>أحدث الطلبات</h2>
        @if($recentOrders->isEmpty())
            <div class="empty">لا توجد طلبات مسجلة حتى الآن</div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>رقم الطلب</th>
                        <th>العميل</th>
                        <th>نوع الاستلام</th>
                        <th>الإجمالي</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($recentOrders as $order)
                        @php($statusLabels = [
                            'pending' => 'جديد',
                            'confirmed' => 'مؤكد',
                            'preparing' => 'قيد التجهيز',
                            'ready' => 'جاهز',
                            'completed' => 'مكتمل',
                            'cancelled' => 'ملغى',
                            'rejected' => 'مرفوض',
                        ])
                        @php($statusClass = in_array($order->status, ['cancelled', 'rejected'], true) ? 'danger' : (in_array($order->status, ['pending', 'ready'], true) ? 'warn' : ''))
                        <tr>
                            <td>
                                <a href="/admin/orders?search={{ urlencode($order->order_number) }}">
                                    <strong>{{ $order->order_number }}</strong>
                                </a>
                            </td>
                            <td>{{ $order->customer_name }}<div class="muted">{{ $order->customer_phone }}</div></td>
                            <td>{{ $order->fulfillment_type === 'pickup' ? 'استلام من المطعم' : 'توصيل' }}</td>
                            <td>{{ $order->total }} د.ك</td>
                            <td><span class="status {{ $statusClass }}">{{ $statusLabels[$order->status] ?? $order->status }}</span></td>
                            <td>{{ $order->created_at?->timezone('Asia/Kuwait')->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
