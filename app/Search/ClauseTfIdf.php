<?php

namespace App\Search;

/** Light TF-IDF + cosine similarity over short texts (port of js/nlu.js). */
class ClauseTfIdf
{
    private const STOPWORDS = [
        'là', 'của', 'và', 'các', 'cho', 'theo', 'khi', 'nào', 'gì', 'có', 'bị', 'người', 'tôi',
        'em', 'anh', 'chị', 'mình', 'muốn', 'hỏi', 'xin', 'nếu', 'thì', 'đã',
        'sẽ', 'được', 'không', 'một', 'những', 'này', 'đó', 'với', 'về', 'trong', 'ra', 'lên',
        'làm', 'sao', 'như', 'thế', 'ạ', 'à', 'nhé', 'vậy', 'hay', 'hoặc', 'tại', 'bao', 'nhiêu',
    ];

    /** Lowercase, strip diacritics (đ -> d), keep a-z0-9 and single spaces. */
    public static function normalize(string $s): string
    {
        $s = VietnameseText::fold($s, true);
        $s = (string) preg_replace('/[^a-z0-9\s]/u', ' ', $s);
        $s = (string) preg_replace('/\s+/u', ' ', $s);

        return trim($s, " \t\n\r\0\x0B");
    }

    /** @return list<string> */
    public static function tokenize(string $s): array
    {
        $out = [];
        foreach (explode(' ', self::normalize($s)) as $t) {
            if ($t !== '' && ! in_array($t, self::STOPWORDS, true)) {
                $out[] = $t;
            }
        }

        return $out;
    }

    /**
     * @param  list<array{id: int, text: string}>  $docs
     * @return array{idf: array<string, float>, vectors: list<array<string, float>>, docs: list<array{id: int, text: string}>}
     */
    public function buildIndex(array $docs): array
    {
        $docTokens = array_map(fn (array $d): array => self::tokenize($d['text']), $docs);
        $df = [];
        foreach ($docTokens as $tokens) {
            foreach (array_unique($tokens) as $t) {
                $df[$t] = ($df[$t] ?? 0) + 1;
            }
        }
        $n = count($docs);
        $idf = [];
        foreach ($df as $term => $count) {
            $idf[$term] = log(($n + 1) / ($count + 1)) + 1;
        }
        $vectors = [];
        foreach ($docTokens as $tokens) {
            $tf = [];
            foreach ($tokens as $t) {
                $tf[$t] = ($tf[$t] ?? 0) + 1;
            }
            $vec = [];
            foreach ($tf as $term => $count) {
                $vec[$term] = ($count / count($tokens)) * ($idf[$term] ?? 0);
            }
            $vectors[] = $vec;
        }

        return ['idf' => $idf, 'vectors' => $vectors, 'docs' => $docs];
    }

    /**
     * Top-k documents by cosine similarity, best first (ties keep document order).
     *
     * @param  array{idf: array<string, float>, vectors: list<array<string, float>>, docs: list<array{id: int, text: string}>}  $index
     * @return list<array{doc: array{id: int, text: string}, score: float}>
     */
    public function search(string $query, array $index, int $k = 5): array
    {
        $q = $this->vectorize($query, $index['idf']);
        $scored = [];
        foreach ($index['docs'] as $i => $doc) {
            $scored[] = ['doc' => $doc, 'score' => $this->cosine($q, $index['vectors'][$i])];
        }
        usort($scored, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $k);
    }

    /**
     * @param  array<string, float>  $idf
     * @return array<string, float>
     */
    private function vectorize(string $text, array $idf): array
    {
        $tokens = self::tokenize($text);
        $tf = [];
        foreach ($tokens as $t) {
            $tf[$t] = ($tf[$t] ?? 0) + 1;
        }
        $vec = [];
        foreach ($tf as $term => $count) {
            if (isset($idf[$term])) {
                $vec[$term] = ($count / count($tokens)) * $idf[$term];
            }
        }

        return $vec;
    }

    /**
     * @param  array<string, float>  $a
     * @param  array<string, float>  $b
     */
    private function cosine(array $a, array $b): float
    {
        $dot = $na = $nb = 0.0;
        foreach ($a as $k => $v) {
            $na += $v * $v;
            if (isset($b[$k])) {
                $dot += $v * $b[$k];
            }
        }
        foreach ($b as $v) {
            $nb += $v * $v;
        }
        if ($na == 0 || $nb == 0) {
            return 0.0;
        }

        return $dot / (sqrt($na) * sqrt($nb));
    }
}
