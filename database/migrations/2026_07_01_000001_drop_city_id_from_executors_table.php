<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropCityIdFromExecutorsTable extends Migration
{
    public function up()
    {
        Schema::table('executors', function (Blueprint $table) {
            if (Schema::hasColumn('executors', 'city_id')) {
                $table->dropColumn('city_id');
            }
        });
    }

    public function down()
    {
        Schema::table('executors', function (Blueprint $table) {
            if (!Schema::hasColumn('executors', 'city_id')) {
                $table->foreignId('city_id')->nullable()->after('user_id');
            }
        });
    }
}
