<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_users', function (Blueprint $table) {
            $table->unique(['chat_id', 'user_id'], 'chat_users_chat_id_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('chat_users', function (Blueprint $table) {
            $table->dropUnique('chat_users_chat_id_user_id_unique');
        });
    }
};
