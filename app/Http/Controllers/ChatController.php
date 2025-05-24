<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * POST /api/chat/messages
     * Сохранить и разослать новое сообщение.
     */
    public function sendMessage(Request $request)
{
    $request->validate(['message' => 'required|string']);

    $message = $request->user()                  // текущий автор
        ->messages()
        ->create([
            'message'         => $request->message,
            'organization_id' => $request->user()->organization_id,
        ]);

    broadcast(new MessageSent($message->load('user.roles')))->toOthers();

    return response()->json(['status' => 'Message sent']);
}


    /**
     * GET /api/chat/messages
     * Вернуть ленту сообщений с данными пользователей.
     */
    public function getMessages()
    {
        $orgId = auth()->user()->organization_id;       // adjust as needed

    return Message::query()
        ->where('organization_id', $orgId)          // 👈 limits to *your* organisation
        ->with('user.roles:id,name')                // eager-load only what the chat needs
        ->latest()
        ->get();
    }

    /**
     * GET /api/chat/users
     * Вернуть только участников чата (уникальных).
     */
    public function getChatUsers()
    {
        return Auth::user()               // если хотите ограничить организацией
            ->organization                //  → уберите две следующие строки,
            ->users()                     //  если это не требуется
            ->whereHas('messages')
            ->select('id', 'first_name', 'last_name')
            ->with('roles:id,name')
            ->distinct()
            ->get();
    }
}
