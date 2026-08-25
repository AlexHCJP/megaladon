<?php

namespace App\Repositories;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\ChatUser;

class ChatRepo
{
    public function createChat()
    {
        return Chat::create([]);
    }

    public function findByMembers(int $userId, int $companionId): ?Chat
    {
        return Chat::whereHas('members', fn ($q) => $q->whereKey($userId))
            ->whereHas('members', fn ($q) => $q->whereKey($companionId))
            ->has('members', '=', 2)
            ->oldest('id')
            ->first();
    }

    public function getChatIdsByUserId(int $userId)
    {
        return ChatUser::where('user_id', $userId)
            ->pluck('chat_id')
            ->toArray();
    }

    public function index(array $chatIds, int $viewerId)
    {
        // Свежие переписки сверху: сортируем по времени последнего сообщения.
        // COALESCE — чтобы только что созданный чат, в котором ещё ничего не
        // написали, не улетал в самый низ (MAX по пустой выборке даёт NULL),
        // а вставал по времени своего создания.
        return Chat::with('members')
            // Счётчик для бейджа: чужие сообщения, которые смотрящий ещё не
            // открывал. Свои в непрочитанные не попадают никогда.
            ->withCount(['messages as unread_count' => function ($query) use ($viewerId) {
                $query->where('user_id', '!=', $viewerId)
                    ->where('is_readed', false);
            }])
            ->whereIn('id', $chatIds)
            // Чат без единого сообщения в списке не показываем: его создали,
            // но переписка так и не началась.
            ->has('messages')
            ->select('chats.*')
            ->selectSub(
                ChatMessage::selectRaw('MAX(created_at)')
                    ->whereColumn('chat_id', 'chats.id'),
                'last_message_at'
            )
            ->orderByRaw('COALESCE(last_message_at, chats.created_at) DESC')
            ->get();
    }

    // Помечает прочитанными чужие сообщения чата. Массовый update идёт мимо
    // модели, поэтому is_readed не нужен в $fillable.
    public function markChatRead(int $chatId, int $viewerId): int
    {
        return ChatMessage::where('chat_id', $chatId)
            ->where('user_id', '!=', $viewerId)
            ->where('is_readed', false)
            ->update(['is_readed' => true]);
    }

    public function indexMessages(int $chatId, array $params)
    {
        $query = ChatMessage::with('user')
            ->where('chat_id', $chatId);
        $query = $this->applyPaginationQuery($query, $params);
        $query = $this->applySortBy($query, $params);
        return $query->get();
    }

    public function storeChatMessage(array $data)
    {
        return ChatMessage::create($data);
    }

    public function editMessage(ChatMessage $chatMessage, array $data)
    {
        return $chatMessage->update($data);
    }

    public function deleteMessage($message_id)
    {
        ChatMessage::find($message_id)->delete();
    }

    private function applyPaginationQuery($query, $params)
    {
        if (isset($params['startRow'])) {
            $query->skip($params['startRow']);
        }
        if (isset($params['rowsPerPage'])) {
            $query->take($params['rowsPerPage']);
        } else {
            $query->take(100);
        }
        return $query;
    }

    private function applySortBy($query, $params)
    {
        $desc = 'ASC';

        if (isset($params['desc'])) {
            $desc = (boolean)$params['desc'] ? 'DESC' : 'ASC';
        }

        if (isset($params['sortBy'])) {
            $query->orderBy($params['sortBy'], $desc);
        }

        return $query;
    }
}