<?php

namespace App\Search;

/** Article search (port of Law.search in js/law.js). */
class LawSearch
{
    public function __construct(
        private readonly ArticleRepository $articles,
        private readonly RankingService $ranking,
    ) {}

    /**
     * Articles matching $q, optionally within one chapter. Ranked hits carry a 'score'; the
     * empty-query and substring-fallback results do not.
     *
     * @return list<array<string, mixed>>
     */
    public function search(string $q, string $chuong = ''): array
    {
        $pool = $this->articles->all();
        if ($chuong !== '') {
            $pool = array_values(array_filter($pool, fn (array $a): bool => $a['chuong'] === $chuong));
        }
        $q = (string) preg_replace('/^[\s\x{FEFF}]+|[\s\x{FEFF}]+$/u', '', $q);
        if ($q === '') {
            return $pool;
        }

        if (preg_match('/^(?:điều|dieu)?\s*([0-9]{1,3})$/iu', $q, $m) === 1) {
            foreach ($pool as $a) {
                if ($a['id'] === (int) $m[1]) {
                    return [$a + ['score' => 1]];
                }
            }
        }

        $min = (float) config('search.search_min_score');
        $ranked = [];
        foreach ($this->ranking->rank($q, $pool)->results as $r) {
            if ($r->score > $min) {
                $ranked[] = $r->article + ['score' => $r->score];
            }
        }
        $ranked = array_slice($ranked, 0, (int) config('search.search_limit'));
        if ($ranked) {
            return $ranked;
        }

        $needle = ClauseTfIdf::normalize($q);

        return array_values(array_filter(
            $pool,
            fn (array $a): bool => str_contains(ClauseTfIdf::normalize(self::str($a['tieu_de']).' '.self::str($a['tu_khoa'])), $needle),
        ));
    }

    /** JS string coercion of a nullable column ("null" when absent). */
    private static function str(mixed $v): string
    {
        return $v === null ? 'null' : (string) $v;
    }
}
