<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatHistory;
use App\Models\Contact;
use App\Models\Note;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-mostly admin area: overview, contact inbox, chatbot questions and the user list.
 * Admins can mark contacts as answered; nothing else here changes data.
 */
class AdminController extends Controller
{
    private const PER_PAGE = 20;

    private const CHART_DAYS = 14;

    public function index(): Response
    {
        $since = now()->subDays(self::CHART_DAYS - 1)->startOfDay();

        $perDay = ChatHistory::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total, SUM(dieu_luat_id IS NULL) as missed')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $chart = collect(range(0, self::CHART_DAYS - 1))->map(function (int $i) use ($since, $perDay): array {
            $day = $since->addDays($i);
            $row = $perDay->get($day->toDateString());

            return [
                'day' => $day->format('d/m'),
                'total' => (int) ($row->total ?? 0),
                'missed' => (int) ($row->missed ?? 0),
            ];
        })->all();

        $questions = ChatHistory::count();
        $missed = ChatHistory::whereNull('dieu_luat_id')->count();

        return Inertia::render('admin/index', [
            'stats' => [
                'users' => User::count(),
                'newUsers' => User::where('created_at', '>=', now()->subDays(7))->count(),
                'notes' => Note::count(),
                'questions' => $questions,
                'questionsToday' => ChatHistory::where('created_at', '>=', now()->startOfDay())->count(),
                'missed' => $missed,
                'missedRate' => $questions > 0 ? round($missed / $questions * 100) : 0,
                'newContacts' => Contact::where('trang_thai', 'moi')->count(),
            ],
            'chart' => $chart,
            'topArticles' => ChatHistory::query()
                ->whereNotNull('dieu_luat_id')
                ->select('dieu_luat_id', DB::raw('COUNT(*) as total'))
                ->groupBy('dieu_luat_id')
                ->orderByDesc('total')
                ->limit(5)
                ->with('article:id,tieu_de')
                ->get()
                ->map(fn (ChatHistory $h): array => [
                    'id' => $h->dieu_luat_id,
                    'tieu_de' => $h->article?->tieu_de,
                    'total' => (int) $h->total,
                ])->all(),
        ]);
    }

    public function contacts(Request $request): Response
    {
        $status = $request->query('trang_thai');
        $status = in_array($status, ['moi', 'da_tra_loi'], true) ? $status : null;

        $contacts = Contact::query()
            ->when($status, fn ($q) => $q->where('trang_thai', $status))
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Contact $c): array => [
                'id' => $c->id,
                'ho_ten' => $c->ho_ten,
                'sdt' => $c->sdt,
                'email' => $c->email,
                'noi_dung' => $c->noi_dung,
                'trang_thai' => $c->trang_thai,
                'created_at' => $c->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/contacts', [
            'contacts' => $contacts,
            'filter' => $status,
        ]);
    }

    public function updateContact(Request $request, Contact $contact): RedirectResponse
    {
        $data = $request->validate([
            'trang_thai' => ['required', Rule::in(['moi', 'da_tra_loi'])],
        ]);

        $contact->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã cập nhật trạng thái thư liên hệ.']);

        return back();
    }

    public function questions(Request $request): Response
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $onlyMissed = $request->query('loai') === 'khong-khop';

        $rows = ChatHistory::query()
            ->with(['user:id,name,email', 'article:id,tieu_de'])
            ->when($onlyMissed, fn ($query) => $query->whereNull('dieu_luat_id'))
            ->when($q !== '', fn ($query) => $query->where('cau_hoi', 'like', '%'.addcslashes($q, '%_\\').'%'))
            ->latest('created_at')
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (ChatHistory $h): array => [
                'id' => $h->id,
                'cau_hoi' => $h->cau_hoi,
                'user' => $h->user?->name,
                'dieu_luat_id' => $h->dieu_luat_id,
                'tieu_de' => $h->article?->tieu_de,
                'khoan_so' => $h->khoan_so,
                'diem' => $h->diem,
                'created_at' => $h->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/questions', [
            'questions' => $rows,
            'filters' => ['q' => $q, 'loai' => $onlyMissed ? 'khong-khop' : 'tat-ca'],
        ]);
    }

    public function users(Request $request): Response
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);

        $users = User::query()
            ->withCount('notes')
            ->when($q !== '', function ($query) use ($q): void {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $query->where(fn ($w) => $w->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('username', 'like', $like));
            })
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (User $u): array => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'username' => $u->username,
                'role' => $u->role,
                'is_demo' => $u->isDemo(),
                'notes_count' => $u->notes_count,
                'created_at' => $u->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/users', [
            'users' => $users,
            'filters' => ['q' => $q],
        ]);
    }
}
