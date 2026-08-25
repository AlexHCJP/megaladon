<?php

namespace App\Presenters\v1;

use App\Presenters\BasePresenter;

class ChatPresenter extends BasePresenter
{
    public function chatList(?int $viewerId = null)
    {
        $lastMessage = $this->lastMessage();
        // Собеседник — участник чата, отличный от смотрящего. Вне HTTP-запроса
        // (например, при вещании события) viewerId передаётся явно, иначе
        // берётся из авторизации.
        $viewerId ??= auth('api')->id();
        $companion = $viewerId
            ? $this->members->firstWhere('id', '!=', $viewerId)
            : null;
        return [
            'id' => $this->id,
            'companion' => $companion
                ? (new UserPresenter($companion))->short()
                : null,
            'lastMessage' => is_null($lastMessage) ? [] : [
                'id' => $lastMessage->id,
                'user_id' => $lastMessage->user_id,
                'message' => $lastMessage->message,
                'file' => $lastMessage->file_url,
                'is_readed' => (boolean) $lastMessage->is_readed,
                'created_at' => $lastMessage->created_at,
            ],
            // Счётчик приходит из ChatRepo::index(). Тот же презентер зовётся
            // из createChat() и NewMessageEvent, где withCount не делался —
            // там непрочитанных по определению нет.
            //
            // Через $this->model, а не $this->unread_count: у BasePresenter
            // есть __get, но нет __isset, поэтому ?? на магическом свойстве
            // всегда отдавал бы дефолт.
            'unread_count' => (int) ($this->model->unread_count ?? 0),
        ];
    }

    public function messages()
    {
        return [
            'id' => $this->id,
            'user' => (new UserPresenter($this->user))->short(),
            'message' => $this->message,
            'file' => $this->file_url ? url($this->file_url) : null,
            'is_readed' => (boolean) $this->is_readed,
            'created_at' => $this->created_at,
        ];
    }
}