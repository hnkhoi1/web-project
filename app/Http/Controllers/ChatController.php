<?php

namespace App\Http\Controllers;

use App\Models\ChatHistory;
use App\Search\ChatConversation;
use App\Search\LawAnswerer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    private const SESSION_KEY = 'chat.pending';

    public function index(Request $request): Response
    {
        $request->session()->forget(self::SESSION_KEY);

        return Inertia::render('chat/index');
    }

    public function ask(Request $request, ChatConversation $conversation, LawAnswerer $answerer): JsonResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:500']]);
        $message = trim($data['message']);
        if ($message === '') {
            return response()->json(['message' => 'Vui lòng nhập câu hỏi.', 'errors' => ['message' => ['Vui lòng nhập câu hỏi.']]], 422);
        }

        $pending = $request->session()->get(self::SESSION_KEY);
        $full = $conversation->resolve($message, is_array($pending) ? $pending : null);
        $answer = $answerer->answer($full);

        $next = $conversation->nextPending($full, $answer);
        if ($next === null) {
            $request->session()->forget(self::SESSION_KEY);
        } else {
            $request->session()->put(self::SESSION_KEY, $next);
        }

        $found = $answer['found'] === true;
        ChatHistory::create([
            'user_id' => $request->user()->id,
            'cau_hoi' => $full,
            'dieu_luat_id' => $found ? $answer['article']['id'] : null,
            'khoan_so' => $found ? ($answer['khoan']['so_khoan'] ?? null) : null,
            'diem' => $found ? $answer['score'] : null,
        ]);

        return response()->json($answer);
    }
}
