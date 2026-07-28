<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = ['chat_id', 'user_id', 'message', 'file_url'];

    // withTrashed: автор сообщения остаётся в истории после удаления аккаунта.
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }
}
