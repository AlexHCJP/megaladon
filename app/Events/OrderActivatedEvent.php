<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Заказ прошёл модерацию: статус сменился с MODERATE на ACTIVE.
 * Диспатчится из OrderObserver при сохранении модели.
 */
class OrderActivatedEvent
{
    use Dispatchable, SerializesModels;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }
}
