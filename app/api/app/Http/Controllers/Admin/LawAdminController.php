<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LawArticleRequest;
use App\Models\Article;
use App\Models\Chapter;
use App\Models\ChatHistory;
use App\Models\Clause;
use App\Models\LawEmbedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Add, edit and delete law articles with their clauses.
 *
 * After the first seed the database is the source of truth: re-running LawSeeder resets the
 * articles that are in database/data/dieu_luat_data.php back to the file's text.
 */
class LawAdminController extends Controller
{
    private const ARTICLE_FIELDS = [
        'tieu_de', 'tom_tat', 'tu_khoa', 'khung_hinh_phat', 'can_cu_boi_thuong', 'ghi_chu_quyen_loi',
    ];

    private const CLAUSE_FIELDS = [
        'so_khoan', 'dieu_kien', 'noi_dung', 'quyen_loi', 'tu_khoa',
        'ti_le_ton_thuong_min', 'ti_le_ton_thuong_max',
        'gia_tri_tai_san_min', 'gia_tri_tai_san_max',
        'so_nguoi_chet_min', 'so_nguoi_chet_max', 'cau_hoi_lam_ro',
    ];

    public function index(Request $request): Response
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);

        $articles = Article::query()
            ->with('chapter:id,ten_chuong')
            ->withCount('clauses')
            ->addSelect(['answers_count' => ChatHistory::query()
                ->selectRaw('COUNT(*)')
                ->whereColumn('lich_su_hoi_dap.dieu_luat_id', 'dieu_luat.id')])
            ->when($q !== '', function ($query) use ($q): void {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $query->where(fn ($w) => $w->where('tieu_de', 'like', $like)
                    ->orWhere('id', ctype_digit($q) ? (int) $q : -1));
            })
            ->orderBy('id')
            ->get()
            ->map(fn (Article $a): array => [
                'id' => (int) $a->id,
                'tieu_de' => $a->tieu_de,
                'chuong' => $a->chapter->ten_chuong,
                'clauses_count' => (int) $a->clauses_count,
                'answers_count' => (int) $a->getAttribute('answers_count'),
                'updated_at' => $a->updated_at?->toIso8601String(),
            ])
            ->all();

        return Inertia::render('admin/laws/index', [
            'articles' => $articles,
            'filters' => ['q' => $q],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/laws/form', [
            'article' => null,
            'chapters' => $this->chapterNames(),
        ]);
    }

    public function store(LawArticleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $article = DB::transaction(fn (): Article => $this->save(new Article(['id' => (int) $data['id']]), $data));

        Inertia::flash('toast', ['type' => 'success', 'message' => "Đã thêm Điều {$article->id}."]);

        return to_route('admin.laws.index');
    }

    public function edit(Article $article): Response
    {
        $article->load(['chapter', 'clauses']);

        return Inertia::render('admin/laws/form', [
            'article' => [
                'id' => (int) $article->id,
                'chuong' => $article->chapter->ten_chuong,
                ...$article->only(self::ARTICLE_FIELDS),
                'khoan' => $article->clauses->map(fn (Clause $c): array => $c->only(self::CLAUSE_FIELDS))->all(),
            ],
            'chapters' => $this->chapterNames(),
        ]);
    }

    public function update(LawArticleRequest $request, Article $article): RedirectResponse
    {
        DB::transaction(fn (): Article => $this->save($article, $request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => "Đã lưu Điều {$article->id}."]);

        return to_route('admin.laws.index');
    }

    public function destroy(Article $article): RedirectResponse
    {
        // Deleting would null the link on these answers and count them as "not found" in the stats.
        $answers = ChatHistory::where('dieu_luat_id', $article->id)->count();
        if ($answers > 0) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => "Không xóa được Điều {$article->id}: chatbot đã dùng điều này trong {$answers} câu trả lời. Hãy sửa nội dung thay vì xóa.",
            ]);

            return back();
        }

        DB::transaction(function () use ($article): void {
            $chapterId = (int) $article->chuong_id;
            $article->delete(); // Clauses and embeddings cascade.
            $this->dropEmptyChapter($chapterId);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Đã xóa Điều {$article->id}."]);

        return to_route('admin.laws.index');
    }

    /**
     * Write the article and replace its clauses; must run inside a transaction.
     *
     * @param  array<string, mixed>  $data
     */
    private function save(Article $article, array $data): Article
    {
        $oldChapter = $article->exists ? (int) $article->chuong_id : null;

        $name = (string) $data['chuong'];
        $chapter = Chapter::firstWhere('ten_chuong', $name)
            ?? Chapter::create(['ten_chuong' => $name, 'so_thu_tu' => (int) Chapter::max('so_thu_tu') + 1]);

        $article->fill(['chuong_id' => $chapter->id]);
        foreach (self::ARTICLE_FIELDS as $field) {
            $article->setAttribute($field, $data[$field] ?? null);
        }
        $article->touch(); // Saves, and bumps updated_at even when only clauses changed.

        $numbers = [];
        foreach ($data['khoan'] as $row) {
            $values = [];
            foreach (self::CLAUSE_FIELDS as $field) {
                $values[$field] = $row[$field] ?? null;
            }
            $numbers[] = (int) $values['so_khoan'];
            Clause::updateOrCreate(
                ['dieu_luat_id' => $article->id, 'so_khoan' => (int) $values['so_khoan']],
                $values,
            );
        }

        Clause::where('dieu_luat_id', $article->id)->whereNotIn('so_khoan', $numbers)->delete();
        // Vectors of removed clauses would keep matching; edited text is re-embedded by search:embed.
        LawEmbedding::where('dieu_luat_id', $article->id)
            ->where('so_khoan', '!=', LawEmbedding::ARTICLE)
            ->whereNotIn('so_khoan', $numbers)
            ->delete();

        if ($oldChapter !== null && $oldChapter !== $chapter->id) {
            $this->dropEmptyChapter($oldChapter);
        }

        return $article;
    }

    private function dropEmptyChapter(int $chapterId): void
    {
        if (! Article::where('chuong_id', $chapterId)->exists()) {
            Chapter::whereKey($chapterId)->delete();
        }
    }

    /** @return list<string> */
    private function chapterNames(): array
    {
        return array_values(Chapter::query()->orderBy('so_thu_tu')->pluck('ten_chuong')
            ->map(fn (mixed $name): string => (string) $name)->all());
    }
}
