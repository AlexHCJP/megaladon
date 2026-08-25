<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Chat\CreateChatRequest;
use App\Http\Requests\Chat\EditMessageRequest;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Http\Requests\Chat\IndexMessagesRequest;
use App\Services\v1\ChatService;
use Illuminate\Http\Request;

class ChatController extends ApiController
{
    private ChatService $chatService;

    public function __construct() {
        $this->chatService = new ChatService();
    }

    public function createChat(CreateChatRequest $request)
    {
        $data = $request->validated();
        return $this->result($this->chatService->createChat(
            auth('api')->user(),
            (int) $data['user_id']
        ));
    }

    public function getChats(IndexMessagesRequest $request)
    {
        return $this->result($this->chatService->getChats(auth('api')->user()));
    }

    public function getMessages(IndexMessagesRequest $request, $id)
    {
        $params = $request->validated();
        return $this->result($this->chatService->chatMessages(
            auth('api')->user(),
            $id,
            $params
        ));
    }

    public function markRead($id)
    {
        return $this->result($this->chatService->markRead(
            auth('api')->user(),
            (int) $id
        ));
    }

    public function sendMessage(SendMessageRequest $request)
    {
        $data = $request->validated();
        return $this->result($this->chatService->sendMessage(auth('api')->user(), $data));
    }

    public function editMessage($id, EditMessageRequest $request)
    {
        $data = $request->validated();
        return $this->result($this->chatService->editMessage($id, auth('api')->user(), $data));
    }

    public function deleteMessage($id)
    {
        return $this->result($this->chatService->deleteMessage($id, auth('api')->user()));
    }
}
