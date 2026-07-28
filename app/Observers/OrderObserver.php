<?php

namespace App\Observers;

use App\Events\OrderActivatedEvent;
use App\Models\Order;

class OrderObserver
{
    /**
     * Ловим переход статуса MODERATE -> ACTIVE (модерация в админке
     * идёт прямым save(), сервисного метода для этого нет), поэтому
     * реагируем на уровне модели.
     */
    public function updated(Order $order): void
    {
        if (
            $order->wasChanged('status')
            && (int) $order->getOriginal('status') === Order::STATUS_MODERATE
            && (int) $order->status === Order::STATUS_ACTIVE
        ) {
            OrderActivatedEvent::dispatch($order);
        }
    }
}
