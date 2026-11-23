<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Printer;
use App\Models\PrintJob;

final class PrintJobDispatcher
{
    public function enqueueKitchenTicket(Order $order): PrintJob
    {
        return $this->enqueue($order, 'kitchen', 'kitchen_ticket');
    }

    public function enqueueCustomerReceipt(Order $order): PrintJob
    {
        return $this->enqueue($order, 'cashier', 'customer_receipt');
    }

    public function reprintKitchenTicket(Order $order): PrintJob
    {
        return $this->requeue($order, 'kitchen', 'kitchen_ticket');
    }

    public function reprintCustomerReceipt(Order $order): PrintJob
    {
        return $this->requeue($order, 'cashier', 'customer_receipt');
    }

    private function enqueue(Order $order, string $purpose, string $jobType): PrintJob
    {
        return PrintJob::query()->firstOrCreate(
            ['order_id' => $order->id, 'job_type' => $jobType],
            $this->jobAttributes($order, $purpose, $jobType),
        );
    }

    private function requeue(Order $order, string $purpose, string $jobType): PrintJob
    {
        $existing = PrintJob::query()
            ->where('order_id', $order->id)
            ->where('job_type', $jobType)
            ->first();

        if ($existing && in_array($existing->status, ['pending', 'processing'], true)) {
            return $existing;
        }

        $attributes = $this->jobAttributes($order, $purpose, $jobType);

        if ($existing) {
            $existing->fill($attributes)->save();

            return $existing->fresh();
        }

        return PrintJob::query()->create(array_merge(
            ['order_id' => $order->id, 'job_type' => $jobType],
            $attributes,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function jobAttributes(Order $order, string $purpose, string $jobType): array
    {
        $order->loadMissing(['branch', 'items']);

        $printerId = Printer::query()
            ->where('branch_id', $order->branch_id)
            ->where('purpose', $purpose)
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        return [
            'branch_id' => $order->branch_id,
            'printer_id' => $printerId,
            'status' => 'pending',
            'payload' => $this->buildPayload($order, $jobType),
            'available_at' => now(),
            'attempts' => 0,
            'claimed_at' => null,
            'completed_at' => null,
            'last_error' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(Order $order, string $jobType): array
    {
        $fulfillmentLabel = $order->fulfillment_type === 'pickup' ? 'استلام من المطعم' : 'توصيل';
        $title = $jobType === 'customer_receipt' ? 'فاتورة العميل' : 'تذكرة المطبخ';

        $lines = [
            $order->branch->name,
            $title,
            'طلب: '.$order->order_number,
            'الوقت: '.$order->created_at?->timezone($order->branch->timezone)->format('Y-m-d H:i'),
            'العميل: '.$order->customer_name,
            'الهاتف: '.$order->customer_phone,
            'الاستلام: '.$fulfillmentLabel,
        ];

        if ($order->fulfillment_type === 'delivery') {
            $lines[] = 'المنطقة: '.trim(($order->delivery_governorate ? $order->delivery_governorate.' · ' : '').(string) $order->delivery_area_name);
            $lines[] = 'العنوان: قطعة '.(string) $order->block.'، شارع '.(string) $order->street
                .($order->avenue ? '، جادة '.$order->avenue : '')
                .'، منزل '.(string) $order->house_number
                .($order->floor ? '، دور '.$order->floor : '')
                .($order->apartment ? '، شقة '.$order->apartment : '');
        }

        if (filled($order->customer_notes)) {
            $lines[] = 'ملاحظات: '.$order->customer_notes;
        }

        $lines[] = '--------';

        foreach ($order->items as $item) {
            $lines[] = $item->quantity.' × '.$item->product_name;
            if (filled($item->category_name)) {
                $lines[] = '  ('.$item->category_name.')';
            }
            if ($jobType === 'customer_receipt') {
                $lines[] = '  '.$item->line_total.' د.ك';
            }
        }

        if ($jobType === 'customer_receipt') {
            $lines[] = '--------';
            $lines[] = 'المجموع: '.$order->subtotal.' د.ك';
            $lines[] = 'التوصيل: '.$order->delivery_fee.' د.ك';
            $lines[] = 'الإجمالي: '.$order->total.' د.ك';
        }

        return [
            'document' => $jobType,
            'title' => $title,
            'branch' => $order->branch->name,
            'order_number' => $order->order_number,
            'created_at' => $order->created_at?->timezone($order->branch->timezone)->format('Y-m-d H:i:s'),
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'fulfillment_type' => $order->fulfillment_type,
            'fulfillment_label' => $fulfillmentLabel,
            'delivery_governorate' => $order->delivery_governorate,
            'delivery_area' => $order->delivery_area_name,
            'block' => $order->block,
            'street' => $order->street,
            'avenue' => $order->avenue,
            'house_number' => $order->house_number,
            'floor' => $order->floor,
            'apartment' => $order->apartment,
            'notes' => $order->customer_notes,
            'items' => $order->items->map(fn ($item) => [
                'category' => $item->category_name,
                'name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ])->values()->all(),
            'subtotal' => $order->subtotal,
            'delivery_fee' => $order->delivery_fee,
            'total' => $order->total,
            'currency' => 'KWD',
            'lines' => $lines,
        ];
    }
}
