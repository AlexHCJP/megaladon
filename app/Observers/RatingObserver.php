<?php

namespace App\Observers;

use App\Models\Executor;
use App\Models\Rating;
use App\Models\Store;
use App\Services\v1\ExecutorService;
use App\Services\v1\StoreService;

class RatingObserver
{
    /**
     * Агрегатный rating исполнителя и магазина хранится колонкой, а править
     * отзывы теперь можно из админки напрямую, минуя сервисы API. Поэтому
     * пересчитываем на уровне модели: так покрыты оба пути записи.
     * Существующие ExecutorRatedEvent и StoreRatedEvent остаются как были —
     * повторный пересчёт идемпотентен.
     */
    public function saved(Rating $rating): void
    {
        $this->recalculate($rating->ratingable_type, (int) $rating->ratingable_id);

        if ($rating->wasRecentlyCreated) {
            return;
        }

        // Отзыв могли перевесить на другой объект — у прежнего агрегат
        // тоже устарел. getOriginal внутри saved ещё отдаёт значения
        // до сохранения: syncOriginal вызывается после этого события.
        $changes = $rating->getChanges();

        if (isset($changes['ratingable_type']) || isset($changes['ratingable_id'])) {
            $this->recalculate(
                $rating->getOriginal('ratingable_type'),
                (int) $rating->getOriginal('ratingable_id')
            );
        }
    }

    public function deleted(Rating $rating): void
    {
        $this->recalculate($rating->ratingable_type, (int) $rating->ratingable_id);
    }

    /**
     * Объект отзыва мог быть удалён — тогда пересчитывать нечего.
     */
    private function recalculate(?string $type, int $id): void
    {
        if (is_null($type) || $id === 0) {
            return;
        }

        if ($type === Executor::class) {
            $executor = Executor::find($id);

            if (!is_null($executor)) {
                (new ExecutorService())->updateRating($executor);
            }

            return;
        }

        if ($type === Store::class) {
            $store = Store::find($id);

            if (!is_null($store)) {
                (new StoreService())->updateRating($store);
            }
        }
    }
}
