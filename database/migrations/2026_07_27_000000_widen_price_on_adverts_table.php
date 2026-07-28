<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * price был decimal(8,2) — потолок 999 999.99. Для площадки по металлу это
 * мало: цена лота легко выходит за миллион, и вставка падала с
 * "SQLSTATE[22003]: Numeric value out of range" (500 вместо внятной ошибки).
 * Расширяем до decimal(15,2) — как раз порядок цен без потери копеек
 * (double, как в orders, для денег брать не стоит из-за погрешности).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adverts', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->change();
        });
    }

    public function down(): void
    {
        // Сужение обратно не влезет для уже созданных дорогих объявлений,
        // поэтому сначала прижимаем их к прежнему потолку — иначе откат
        // упадёт с тем же "out of range".
        DB::table('adverts')->where('price', '>', 999999.99)->update(['price' => 999999.99]);

        Schema::table('adverts', function (Blueprint $table) {
            $table->decimal('price', 8, 2)->change();
        });
    }
};
