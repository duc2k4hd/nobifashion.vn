<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\OrderService;

class OrderObserver
{
    public function updated(Order $order): void
    {
        if (!$order->wasChanged('delivery_status')) {
            return;
        }

        $originalStatus = $order->getOriginal('delivery_status');
        $currentStatus = $order->delivery_status;

        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);

        if ($currentStatus === 'delivered' && $originalStatus !== 'delivered') {
            $orderService->handleOrderDelivered($order);
            return;
        }

        if ($originalStatus === 'delivered' && $currentStatus !== 'delivered') {
            $orderService->handleOrderDeliveryReverted($order);
        }
    }
}

