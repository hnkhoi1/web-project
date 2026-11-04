<?php

namespace App\Http\Controllers;

use App\Http\Requests\NoteRequest;
use App\Models\Note;
use App\Models\UserPref;
use App\Search\ArticleRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    public const MAX_NOTES = 200;

    /** Article ids featured on the home page, as on the original site. */
    public const POPULAR_ARTICLE_IDS = [123, 168, 174, 260, 353, 134];

    public function index(Request $request, ArticleRepository $articles): Response
    {
        $byId = collect($articles->all())->keyBy('id');

        return Inertia::render('notes/index', [
            'notes' => $request->user()->notes()
                ->orderByDesc('pinned')
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get(),
            'popular' => collect(self::POPULAR_ARTICLE_IDS)
                ->map(fn (int $id) => $byId->get($id))
                ->filter()
                ->map(fn (array $a): array => [
                    'id' => $a['id'],
                    'tieu_de' => $a['tieu_de'],
                    'tom_tat' => $a['tom_tat'],
                    'khung_hinh_phat' => $a['khung_hinh_phat'],
                ])
                ->values(),
        ]);
    }

    public function store(NoteRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($user->notes()->count() >= self::MAX_NOTES) {
            throw ValidationException::withMessages(['title' => 'Bạn đã đạt tối đa '.self::MAX_NOTES.' ghi chú.']);
        }

        $data = $request->validated();
        $note = new Note([
            'title' => $request->resolvedTitle(),
            'content' => trim((string) ($data['content'] ?? '')),
            'color' => $data['color'] ?? UserPref::payloadFor($user)['noteColor'],
            'pinned' => (bool) ($data['pinned'] ?? false),
            'source_type' => $data['source_type'] ?? null,
            'source_ref' => isset($data['source_type']) ? ($data['source_ref'] ?? null) : null,
        ]);
        $note->user()->associate($user)->save();

        if ($request->wantsJson()) {
            return response()->json(['note' => $note], 201);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã lưu ghi chú.']);

        return to_route('notes.index');
    }

    public function update(NoteRequest $request, Note $note): RedirectResponse
    {
        Gate::authorize('update', $note);

        $data = $request->validated();
        $note->fill([
            'title' => $request->resolvedTitle(),
            'content' => trim((string) ($data['content'] ?? '')),
            'color' => $data['color'] ?? $note->color,
            'pinned' => (bool) ($data['pinned'] ?? $note->pinned),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã lưu ghi chú.']);

        return to_route('notes.index');
    }

    public function pin(Request $request, Note $note): RedirectResponse
    {
        Gate::authorize('update', $note);

        $note->update(['pinned' => $request->boolean('pinned')]);

        return to_route('notes.index');
    }

    public function destroy(Note $note): RedirectResponse
    {
        Gate::authorize('delete', $note);

        $note->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã xóa ghi chú.']);

        return to_route('notes.index');
    }
}
