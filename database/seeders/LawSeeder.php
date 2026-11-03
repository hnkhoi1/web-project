<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Chapter;
use App\Models\Clause;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LawSeeder extends Seeder
{
    private const ARTICLE_FIELDS = [
        'tieu_de', 'tom_tat', 'tu_khoa', 'khung_hinh_phat', 'can_cu_boi_thuong', 'ghi_chu_quyen_loi',
    ];

    private const CLAUSE_FIELDS = [
        'dieu_kien', 'noi_dung', 'quyen_loi', 'tu_khoa',
        'ti_le_ton_thuong_min', 'ti_le_ton_thuong_max',
        'gia_tri_tai_san_min', 'gia_tri_tai_san_max',
        'so_nguoi_chet_min', 'so_nguoi_chet_max', 'cau_hoi_lam_ro',
    ];

    /**
     * Load the law data (idempotent). Absent source keys are stored as NULL.
     */
    public function run(): void
    {
        /** @var list<array<string, mixed>> $articles */
        $articles = require database_path('data/dieu_luat_data.php');

        DB::transaction(function () use ($articles): void {
            $chapterIds = [];

            foreach ($articles as $row) {
                $name = (string) $row['chuong'];
                // so_thu_tu follows first-seen chapter order in the source.
                $chapterIds[$name] ??= Chapter::updateOrCreate(
                    ['ten_chuong' => $name],
                    ['so_thu_tu' => count($chapterIds) + 1],
                )->id;

                $article = Article::updateOrCreate(
                    ['id' => $row['id']],
                    ['chuong_id' => $chapterIds[$name]] + $this->pick($row, self::ARTICLE_FIELDS),
                );

                $numbers = [];
                foreach ($row['khoan'] as $clause) {
                    $numbers[] = $clause['so_khoan'];
                    Clause::updateOrCreate(
                        ['dieu_luat_id' => $article->id, 'so_khoan' => $clause['so_khoan']],
                        $this->pick($clause, self::CLAUSE_FIELDS),
                    );
                }

                Clause::where('dieu_luat_id', $article->id)->whereNotIn('so_khoan', $numbers)->delete();
            }
        });
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    private function pick(array $row, array $fields): array
    {
        $out = [];
        foreach ($fields as $field) {
            $out[$field] = $row[$field] ?? null;
        }

        return $out;
    }
}
