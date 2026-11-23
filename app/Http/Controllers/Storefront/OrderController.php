<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\DeliveryArea;
use App\Models\Order;
use App\Models\Product;
use App\Services\PrintJobDispatcher;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function store(Request $request, PrintJobDispatcher $printJobDispatcher, string $branchCode): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'fulfillment_type' => ['required', 'in:pickup,delivery'],
            'delivery_area_id' => ['required_if:fulfillment_type,delivery', 'nullable', 'integer', 'exists:delivery_areas,id'],
            'block' => ['required_if:fulfillment_type,delivery', 'nullable', 'string', 'max:30'],
            'street' => ['required_if:fulfillment_type,delivery', 'nullable', 'string', 'max:100'],
            'avenue' => ['nullable', 'string', 'max:100'],
            'house_number' => ['required_if:fulfillment_type,delivery', 'nullable', 'string', 'max:50'],
            'floor' => ['nullable', 'string', 'max:30'],
            'apartment' => ['nullable', 'string', 'max:30'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:40'],
            'items.*.product_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $branch = Branch::query()
            ->where('code', strtoupper($branchCode))
            ->where('is_active', true)
            ->firstOrFail();

        $trackingToken = Str::random(64);

        $order = DB::transaction(function () use ($data, $branch, $trackingToken, $request, $printJobDispatcher): Order {
            $area = null;
            $deliveryFee = 0;
            $minimumOrder = 0;

            if ($data['fulfillment_type'] === 'delivery') {
                $area = DeliveryArea::query()
                    ->whereKey($data['delivery_area_id'])
                    ->where('branch_id', $branch->id)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (! $area) {
                    throw ValidationException::withMessages([
                        'delivery_area_id' => ['منطقة التوصيل غير متاحة لهذا الفرع.'],
                    ]);
                }

                $deliveryFee = $this->toFils((string) $area->delivery_fee);
                $minimumOrder = $this->toFils((string) $area->minimum_order);
            }

            $quantities = collect($data['items'])->keyBy('product_id');
            $products = Product::query()
                ->where('branch_id', $branch->id)
                ->whereIn('id', $quantities->keys())
                ->where('is_active', true)
                ->whereHas('category', fn ($query) => $query->where('is_active', true))
                ->with(['category:id,name', 'availabilityWindows'])
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $quantities->count()) {
                throw ValidationException::withMessages([
                    'items' => ['يوجد صنف غير متاح أو لا يتبع هذا الفرع. حدّث السلة وحاول مرة أخرى.'],
                ]);
            }

            $now = Carbon::now($branch->timezone);
            $subtotal = 0;

            foreach ($quantities as $productId => $item) {
                $product = $products->get($productId);
                $windows = $product->availabilityWindows;

                if ($windows->isNotEmpty() && ! $windows->contains(function ($window) use ($now): bool {
                    return $window->is_active
                        && $now->format('H:i:s') >= $window->start_time
                        && $now->format('H:i:s') < $window->end_time;
                })) {
                    throw ValidationException::withMessages([
                        'items' => ["الصنف {$product->name} غير متاح في الوقت الحالي."],
                    ]);
                }

                $subtotal += $this->toFils((string) $product->price) * (int) $item['quantity'];
            }

            if ($data['fulfillment_type'] === 'delivery' && $subtotal < $minimumOrder) {
                throw ValidationException::withMessages([
                    'items' => ['قيمة الطلب أقل من الحد الأدنى لمنطقة التوصيل.'],
                ]);
            }

            $order = Order::query()->create([
                'branch_id' => $branch->id,
                'delivery_area_id' => $area?->id,
                'order_number' => 'TEMP-'.Str::uuid(),
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'fulfillment_type' => $data['fulfillment_type'],
                'status' => 'pending',
                'payment_method' => 'cash',
                'payment_status' => 'unpaid',
                'delivery_governorate' => $area?->governorate,
                'delivery_area_name' => $area?->name,
                'block' => $data['fulfillment_type'] === 'delivery' ? $data['block'] : null,
                'street' => $data['fulfillment_type'] === 'delivery' ? $data['street'] : null,
                'avenue' => $data['fulfillment_type'] === 'delivery' ? ($data['avenue'] ?? null) : null,
                'house_number' => $data['fulfillment_type'] === 'delivery' ? $data['house_number'] : null,
                'floor' => $data['fulfillment_type'] === 'delivery' ? ($data['floor'] ?? null) : null,
                'apartment' => $data['fulfillment_type'] === 'delivery' ? ($data['apartment'] ?? null) : null,
                'customer_notes' => $data['customer_notes'] ?? null,
                'subtotal' => $this->fromFils($subtotal),
                'delivery_fee' => $this->fromFils($deliveryFee),
                'minimum_order_snapshot' => $this->fromFils($minimumOrder),
                'total' => $this->fromFils($subtotal + $deliveryFee),
                'tracking_token_hash' => hash('sha256', $trackingToken),
            ]);

            $order->update([
                'order_number' => $branch->code.'-'.Carbon::now($branch->timezone)->format('ymd').'-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
            ]);

            foreach ($quantities as $productId => $item) {
                $product = $products->get($productId);
                $quantity = (int) $item['quantity'];
                $unitPrice = $this->toFils((string) $product->price);

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'category_name' => $product->category->name,
                    'unit_price' => $this->fromFils($unitPrice),
                    'quantity' => $quantity,
                    'line_total' => $this->fromFils($unitPrice * $quantity),
                ]);
            }

            $printJobDispatcher->enqueueKitchenTicket($order);

            AuditLog::query()->create([
                'branch_id' => $branch->id,
                'actor_id' => null,
                'action' => 'order.created',
                'auditable_type' => Order::class,
                'auditable_id' => $order->id,
                'after_values' => [
                    'order_number' => $order->order_number,
                    'fulfillment_type' => $order->fulfillment_type,
                    'subtotal' => $order->subtotal,
                    'delivery_fee' => $order->delivery_fee,
                    'total' => $order->total,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'created_at' => now(),
            ]);

            return $order;
        });

        return response()->json([
            'message' => 'تم استلام طلبك بنجاح.',
            'order' => $order->load('items'),
            'tracking_token' => $trackingToken,
        ], 201);
    }

    public function track(Request $request, string $branchCode, string $orderNumber): JsonResponse
    {
        $token = $request->header('X-Order-Tracking-Token');

        if (! is_string($token) || $token === '') {
            abort(401, 'رمز تتبع الطلب مطلوب.');
        }

        $branch = Branch::query()
            ->where('code', strtoupper($branchCode))
            ->firstOrFail();

        $order = Order::query()
            ->where('branch_id', $branch->id)
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        if (! hash_equals($order->tracking_token_hash, hash('sha256', $token))) {
            abort(404);
        }

        return response()->json([
            'order' => $order->load('items'),
        ]);
    }

    private function toFils(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 1000) + (int) str_pad(substr($fraction, 0, 3), 3, '0');
    }

    private function fromFils(int $amount): string
    {
        return intdiv($amount, 1000).'.'.str_pad((string) ($amount % 1000), 3, '0', STR_PAD_LEFT);
    }
}
