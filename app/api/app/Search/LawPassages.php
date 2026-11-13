<?php

namespace App\Search;

use App\Models\LawEmbedding;

/**
 * The texts that get a vector: one per article and one per clause. Shared by `search:embed`
 * (which stores them) and `search:status` (which only compares them with what is stored).
 */
class LawPassages
{
    /**
     * @return list<array{0: int, 1: int, 2: string}> [article id, clause number (0 for the article), text]
     */
    public static function all(ArticleRepository $articles): array
    {
        $out = [];

        foreach ($articles->all() as $article) {
            $out[] = [$article['id'], LawEmbedding::ARTICLE, self::join([$article['tieu_de'], $article['tom_tat'], $article['tu_khoa']])];
            foreach ($article['khoan'] as $clause) {
                $out[] = [$article['id'], $clause['so_khoan'], self::join([$clause['dieu_kien'], $clause['noi_dung'], $clause['tu_khoa']])];
            }
        }

        return $out;
    }

    /** @param  list<string|null>  $parts */
    private static function join(array $parts): string
    {
        return trim(implode("\n", array_map(fn (?string $p): string => (string) $p, $parts)));
    }
}
