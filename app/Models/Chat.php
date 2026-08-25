<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [];

    // withTrashed: собеседник остаётся в чате после удаления своего аккаунта.
    public function members()
    {
        return $this->belongsToMany(User::class, 'chat_users', 'chat_id', 'user_id')->withTrashed();
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'chat_id', 'id');
    }

    public function lastMessage()
    {
        return $this->hasOne(ChatMessage::class, 'chat_id', 'id')->latest('created_at')->first();;
    }
}
