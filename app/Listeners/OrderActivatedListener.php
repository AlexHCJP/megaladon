<?php

namespace App\Listeners;

use App\Events\OrderActivatedEvent;
use App\Services\v1\PushService;
use Illuminate\Support\Facades\Log;

class OrderActivatedListener
{
    public function __construct(private PushService $push)
    {
    }

    public function handle(OrderActivatedEvent $event): void
    {
        $order = $event->order;
        if (!$order->user) {
            return;
        }

        // Пуш — вспомогательное действие: его сбой (недоступность FCM,
        // невалидный токен) не должен ломать модерацию заказа, которая
        // при QUEUE_CONNECTION=sync выполняется в том же запросе.
        try {
            $this->push->send(
                $order->user,
                'Заказ №' . $order->id,
                'Ваш заказ прошёл модерацию и опубликован',
                ['type' => 'order_activated', 'order_id' => $order->id],
            );
        } catch (\Throwable $e) {
            Log::error(__METHOD__ . ' push failed for order #' . $order->id . ': ' . $e->getMessage());
        }
    }
}
