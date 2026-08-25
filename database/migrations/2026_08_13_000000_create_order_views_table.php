<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Что пользователь последний раз видел в заказе. Строка появляется в
     * момент, когда заказ попадает к нему в списки (создал сам, назначили
     * исполнителем) или когда он открыл карточку. Отсутствие строки означает
     * «нового нет» — иначе на старых заказах после релиза вспыхнули бы бейджи.
     */
    public function up(): void
    {
        Schema::create('order_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedTinyInteger('seen_status');
            $table->unsignedInteger('seen_offers_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'order_id'], 'order_views_user_id_order_id_unique');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_views');
    }
};
