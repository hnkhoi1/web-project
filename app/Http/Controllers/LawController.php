<?php

namespace App\Http\Controllers;

use App\Search\ArticleRepository;
use App\Search\LawSearch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LawController extends Controller
{
    private const MAX_QUERY_LENGTH = 200;

    public function search(Request $request, LawSearch $search, ArticleRepository $articles): Response
    {
        $chapters = $articles->chapters();
        $q = mb_substr(trim($this->string($request->query('q'))), 0, self::MAX_QUERY_LENGTH);
        $chuong = $this->string($request->query('chuong'));
        if (! in_array($chuong, $chapters, true)) {
            $chuong = '';
        }

        return Inertia::render('law/search', [
            'q' => $q,
            'chuong' => $chuong,
            'chapters' => $chapters,
            'results' => array_map(
                fn (array $a): array => array_diff_key($a, ['khoan' => 1, 'tu_khoa' => 1]),
                $search->search($q, $chuong),
            ),
        ]);
    }

    public function show(int $id, ArticleRepository $articles): Response
    {
        $article = $articles->find($id) ?? abort(404);

        return Inertia::render('law/show', [
            'article' => $article,
            'related' => array_map(
                fn (array $a): array => ['id' => $a['id'], 'tieu_de' => $a['tieu_de']],
                $articles->related($article, 4),
            ),
        ]);
    }

    /** Query-string value as a valid UTF-8 string; arrays and invalid UTF-8 count as empty. */
    private function string(mixed $value): string
    {
        return is_string($value) && mb_check_encoding($value, 'UTF-8') ? $value : '';
    }
}
