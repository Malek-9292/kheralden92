<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\DeliveryArea;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderWorkflow;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function store(Request $request, string $branchCode): JsonResponse
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
            'idempotency_key' => ['nullable', 'string', 'min:16', 'max:128'],
            'items' => ['required', 'array', 'min:1', 'max:40'],
            'items.*.product_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $branch = Branch::query()
            ->where('code', strtoupper($branchCode))
            ->where('is_active', true)
            ->firstOrFail();

        if ($data['fulfillment_type'] === 'delivery') {
            $deliveryPaused = filter_var(
                BranchSetting::query()
                    ->where('branch_id', $branch->id)
                    ->where('key', 'delivery_paused')
                    ->value('value'),
                FILTER_VALIDATE_BOOLEAN,
            );

            if ($deliveryPaused) {
                $reason = BranchSetting::query()
                    ->where('branch_id', $branch->id)
                    ->where('key', 'delivery_pause_reason')
                    ->value('value');

                throw ValidationException::withMessages([
                    'fulfillment_type' => [
                        filled($reason)
                            ? 'التوصيل متوقف مؤقتًا: '.$reason
                            : 'التوصيل متوقف مؤقتًا. يمكنك الطلب للاستلام من المطعم.',
                    ],
                ]);
            }
        }

        $idempotencyKey = $data['idempotency_key']
            ?? (is_string($request->header('Idempotency-Key')) ? $request->header('Idempotency-Key') : null);
        $idempotencyHash = filled($idempotencyKey) ? hash('sha256', $branch->id.'|'.$idempotencyKey) : null;

        if ($idempotencyHash !== null) {
            $existing = Order::query()
                ->where('branch_id', $branch->id)
                ->where('idempotency_key_hash', $idempotencyHash)
                ->first();

            if ($existing) {
                return response()->json([
                    'message' => 'تم استلام طلبك مسبقًا.',
                    'order' => $existing->load('items'),
                    'tracking_token' => null,
                    'idempotent_replay' => true,
                ]);
            }
        }

        $trackingToken = Str::random(64);

        $order = DB::transaction(function () use ($data, $branch, $trackingToken, $idempotencyHash, $request): Order {
            $workflow = app(OrderWorkflow::class);
            $area = null;
            $deliveryFee = 0;
            $minimumOrder = 0;

            if ($data['fulfillment_type'] === 'delivery') {
                $area = DeliveryArea::query()
                    ->whereKey($data['delivery_area_id'])
                    ->where('branch_id', $branch->id)
                    ->where('is_active', true)
                    ->where(function ($query) {
                        $query->whereNull('delivery_area_group_id')
                            ->orWhereHas('group', fn ($group) => $group->where('is_active', true));
                    })
                    ->lockForUpdate()
                    ->first();

                if (!$area) {
                    throw ValidationException::withMessages([
                        'delivery_area_id' => ['منطقة التوصيل غير متاحة لهذا الفرع.'],
                    ]);
                }

                $deliveryFee = Money::toFils($area->effectiveDeliveryFee());
                $minimumOrder = Money::toFils($area->effectiveMinimumOrder());
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

                if ($windows->isNotEmpty() && !$windows->contains(function ($window) use ($now): bool {
                    return $window->is_active
                        && $now->format('H:i:s') >= $window->start_time
                        && $now->format('H:i:s') < $window->end_time;
                })) {
                    throw ValidationException::withMessages([
                        'items' => ["الصنف {$product->name} غير متاح في الوقت الحالي."],
                    ]);
                }

                $subtotal += Money::multiplyFils(
                    Money::toFils((string) $product->price),
                    (int) $item['quantity'],
                );
            }

            if ($data['fulfillment_type'] === 'delivery' && $subtotal < $minimumOrder) {
                throw ValidationException::withMessages([
                    'items' => ['قيمة الطلب أقل من الحد الأدنى لمنطقة التوصيل.'],
                ]);
            }

            $order = Order::query()->create([
                'branch_id' => $branch->id,
                'delivery_area_id' => $area?->id,
                // Keep the temporary value within the 40-character column limit.
                'order_number' => 'TEMP-'.Str::random(32),
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
                'subtotal' => Money::fromFils($subtotal),
                'delivery_fee' => Money::fromFils($deliveryFee),
                'minimum_order_snapshot' => Money::fromFils($minimumOrder),
                'total' => Money::fromFils(Money::addFils($subtotal, $deliveryFee)),
                'tracking_token_hash' => hash('sha256', $trackingToken),
                'idempotency_key_hash' => $idempotencyHash,
            ]);

            $order->update([
                'order_number' => $branch->code.'-'.Carbon::now($branch->timezone)->format('ymd').'-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
            ]);

            foreach ($quantities as $productId => $item) {
                $product = $products->get($productId);
                $quantity = (int) $item['quantity'];
                $unitPrice = Money::toFils((string) $product->price);

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'category_name' => $product->category->name,
                    'unit_price' => Money::fromFils($unitPrice),
                    'quantity' => $quantity,
                    'line_total' => Money::fromFils(Money::multiplyFils($unitPrice, $quantity)),
                ]);
            }

            // Cash (current checkout): auto-confirm + kitchen ticket in this same
            // transaction. Unpaid online payments would stay pending until paid.
            $order = $workflow->acceptOnPlacement($order->fresh()->load('items'));

            AuditLog::query()->create([
                'branch_id' => $branch->id,
                'actor_id' => null,
                'action' => 'order.created',
                'auditable_type' => Order::class,
                'auditable_id' => $order->id,
                'after_values' => [
                    'order_number' => $order->order_number,
                    'fulfillment_type' => $order->fulfillment_type,
                    'status' => $order->status,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'subtotal' => $order->subtotal,
                    'delivery_fee' => $order->delivery_fee,
                    'total' => $order->total,
                    'kitchen_print_enqueued' => $order->status === 'confirmed',
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
}
