<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PrintJob;
use App\Models\Printer;

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

    private function enqueue(Order $order, string $purpose, string $jobType): PrintJob
    {
        $order->loadMissing(['branch', 'items']);

        $printerId = Printer::query()
            ->where('branch_id', $order->branch_id)
            ->where('purpose', $purpose)
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        $payload = [
            'document' => $jobType,
            'branch' => $order->branch->name,
            'order_number' => $order->order_number,
            'created_at' => $order->created_at?->timezone($order->branch->timezone)->format('Y-m-d H:i:s'),
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'fulfillment_type' => $order->fulfillment_type,
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
        ];

        return PrintJob::query()->firstOrCreate(
            ['order_id' => $order->id, 'job_type' => $jobType],
            [
                'branch_id' => $order->branch_id,
                'printer_id' => $printerId,
                'status' => 'pending',
                'payload' => $payload,
                'available_at' => now(),
            ],
        );
    }
}
