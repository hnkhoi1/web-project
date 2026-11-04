<?php

namespace App\Search;

use App\Models\Article;
use App\Models\Chapter;
use App\Models\Clause;

/**
 * Plain-array view of the law data for the search scorer.
 *
 * Article shape: id, chuong (chapter name), tieu_de, tom_tat, tu_khoa, khung_hinh_phat,
 * can_cu_boi_thuong, ghi_chu_quyen_loi, khoan (list of clauses). Absent values are null.
 */
class ArticleRepository
{
    /** @var list<array<string, mixed>>|null */
    private ?array $cache = null;

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return $this->cache ??= Article::query()
            ->with(['chapter', 'clauses'])
            ->orderBy('id')
            ->get()
            ->map(fn (Article $a): array => [
                'id' => (int) $a->id,
                'chuong' => $a->chapter->ten_chuong,
                'tieu_de' => $a->tieu_de,
                'tom_tat' => $a->tom_tat,
                'tu_khoa' => $a->tu_khoa,
                'khung_hinh_phat' => $a->khung_hinh_phat,
                'can_cu_boi_thuong' => $a->can_cu_boi_thuong,
                'ghi_chu_quyen_loi' => $a->ghi_chu_quyen_loi,
                'khoan' => $a->clauses->map(fn (Clause $c): array => [
                    'so_khoan' => $c->so_khoan,
                    'tu_khoa' => $c->tu_khoa,
                    'dieu_kien' => $c->dieu_kien,
                    'ti_le_ton_thuong_min' => $c->ti_le_ton_thuong_min,
                    'ti_le_ton_thuong_max' => $c->ti_le_ton_thuong_max,
                    'gia_tri_tai_san_min' => $c->gia_tri_tai_san_min,
                    'gia_tri_tai_san_max' => $c->gia_tri_tai_san_max,
                    'so_nguoi_chet_min' => $c->so_nguoi_chet_min,
                    'so_nguoi_chet_max' => $c->so_nguoi_chet_max,
                    'noi_dung' => $c->noi_dung,
                    'quyen_loi' => $c->quyen_loi,
                    'cau_hoi_lam_ro' => $c->cau_hoi_lam_ro,
                ])->all(),
            ])
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        foreach ($this->all() as $article) {
            if ($article['id'] === $id) {
                return $article;
            }
        }

        return null;
    }

    /** @return list<string> Chapter names in book order. */
    public function chapters(): array
    {
        return Chapter::query()->orderBy('so_thu_tu')->pluck('ten_chuong')->all();
    }

    /**
     * Other articles in the same chapter, by id.
     *
     * @param  array<string, mixed>  $article
     * @return list<array<string, mixed>>
     */
    public function related(array $article, int $n = 4): array
    {
        $same = array_filter(
            $this->all(),
            fn (array $a): bool => $a['chuong'] === $article['chuong'] && $a['id'] !== $article['id'],
        );

        return array_slice(array_values($same), 0, max(0, $n));
    }
}
