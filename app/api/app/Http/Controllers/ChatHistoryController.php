<?php

namespace App\Http\Controllers;

use App\Models\ChatHistory;
use App\Search\LawAnswerer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A user's own chatbot questions: browse them, reopen an answer, rate it, delete it.
 * Every query is scoped to the signed-in user, so another user's id is simply a 404.
 */
class ChatHistoryController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): Response
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);

        $rows = $request->user()->chatHistories()
            ->with('article:id,tieu_de')
            ->when($q !== '', function ($query) use ($q): void {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $query->where(fn ($w) => $w->where('tin_nhan', 'like', $like)->orWhere('cau_hoi', 'like', $like));
            })
            ->latest('created_at')
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (ChatHistory $h): array => [
                'id' => $h->id,
                'cau_hoi' => $h->displayQuestion(),
                'dieu_luat_id' => $h->dieu_luat_id,
                'tieu_de' => $h->article?->tieu_de,
                'khoan_so' => $h->khoan_so,
                'danh_gia' => $h->danh_gia,
                'created_at' => $h->created_at?->toIso8601String(),
            ]);

        return Inertia::render('chat/history', [
            'history' => $rows,
            'filters' => ['q' => $q],
        ]);
    }

    /** Reopen a past question: the answer is computed again, which gives the same result for the same question. */
    public function show(Request $request, int $history, LawAnswerer $answerer): Response
    {
        $row = $this->own($request, $history);

        return Inertia::render('chat/history-show', [
            'item' => [
                'id' => $row->id,
                'cau_hoi' => $row->displayQuestion(),
                'danh_gia' => $row->danh_gia,
                'created_at' => $row->created_at?->toIso8601String(),
            ],
            'answer' => $answerer->answer($row->cau_hoi),
        ]);
    }

    public function rate(Request $request, int $history): JsonResponse|RedirectResponse
    {
        $row = $this->own($request, $history);

        $data = $request->validate([
            'danh_gia' => ['nullable', Rule::in([ChatHistory::RATING_CORRECT, ChatHistory::RATING_WRONG])],
        ]);

        $row->danh_gia = $data['danh_gia'] ?? null;
        $row->save();

        if ($request->expectsJson()) {
            return response()->json(['danh_gia' => $row->danh_gia]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã ghi nhận đánh giá của bạn.']);

        return back();
    }

    public function destroy(Request $request, int $history): RedirectResponse
    {
        $this->own($request, $history)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã xóa câu hỏi khỏi lịch sử.']);

        return to_route('history.index');
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->user()->chatHistories()->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã xóa toàn bộ lịch sử câu hỏi.']);

        return to_route('history.index');
    }

    private function own(Request $request, int $id): ChatHistory
    {
        return $request->user()->chatHistories()->findOrFail($id);
    }
}
