<?php
/**
 * TfidfMatcher.php
 * -----------------------------------------------------------------
 * NLU nhẹ dùng TF-IDF + cosine similarity, thay cho sentence-transformers
 * (không cần mô hình nặng / GPU, chạy được trên hosting PHP phổ thông).
 *
 * Nâng cấp lên embedding thật: xem README.md mục "Nâng cấp NLU" —
 * ý tưởng là thay hàm vectorize() bằng lời gọi tới một dịch vụ Python
 * nhỏ (FastAPI + sentence-transformers, model đa ngôn ngữ nhẹ như
 * "paraphrase-multilingual-MiniLM-L12-v2") và giữ nguyên cosine similarity
 * ở đây để so khớp vector trả về.
 * -----------------------------------------------------------------
 */

final class TfidfMatcher
{
    /** @var array<string> */
    private static array $stopwords = [
        'la','cua','va','cac','cho','theo','khi','nao','gi','co','bi','nguoi','toi',
        'em','anh','chi','minh','muon','hoi','xin','neu','thi','da','se','duoc','khong',
        'mot','nhung','nay','do','voi','ve','trong','ra','len','lam','sao','nhu','the',
        'a','a','nhe','vay','hay','hoac','tai','bao','nhieu'
    ];

    /** Bỏ dấu tiếng Việt + hạ chữ thường + chỉ giữ chữ/số. */
    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $map = [
            'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a',
            'ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
            'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
            'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
            'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o',
            'ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
            'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
            'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d',
        ];
        $text = strtr($text, $map);
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /** @return array<string> */
    public static function tokenize(string $text): array
    {
        $tokens = explode(' ', self::normalize($text));
        return array_values(array_filter($tokens, fn($t) => $t !== '' && !in_array($t, self::$stopwords, true)));
    }

    /**
     * Xây chỉ mục TF-IDF.
     * @param array<int, array{id:int, text:string}> $docs
     * @return array{idf: array<string,float>, vectors: array<int, array<string,float>>, docs: array}
     */
    public static function buildIndex(array $docs): array
    {
        $docTokens = array_map(fn($d) => self::tokenize($d['text']), $docs);
        $df = [];
        foreach ($docTokens as $tokens) {
            foreach (array_unique($tokens) as $t) {
                $df[$t] = ($df[$t] ?? 0) + 1;
            }
        }
        $N = count($docs);
        $idf = [];
        foreach ($df as $term => $count) {
            $idf[$term] = log(($N + 1) / ($count + 1)) + 1;
        }

        $vectors = [];
        foreach ($docTokens as $tokens) {
            $tf = array_count_values($tokens);
            $len = max(count($tokens), 1);
            $vec = [];
            foreach ($tf as $term => $count) {
                $vec[$term] = ($count / $len) * ($idf[$term] ?? 0);
            }
            $vectors[] = $vec;
        }

        return ['idf' => $idf, 'vectors' => $vectors, 'docs' => $docs];
    }

    /** @return array<string,float> */
    public static function vectorize(string $text, array $idf): array
    {
        $tokens = self::tokenize($text);
        $tf = array_count_values($tokens);
        $len = max(count($tokens), 1);
        $vec = [];
        foreach ($tf as $term => $count) {
            if (isset($idf[$term])) {
                $vec[$term] = ($count / $len) * $idf[$term];
            }
        }
        return $vec;
    }

    public static function cosine(array $a, array $b): float
    {
        $dot = 0.0; $na = 0.0; $nb = 0.0;
        foreach ($a as $k => $v) {
            $na += $v * $v;
            if (isset($b[$k])) $dot += $v * $b[$k];
        }
        foreach ($b as $v) { $nb += $v * $v; }
        if ($na == 0.0 || $nb == 0.0) return 0.0;
        return $dot / (sqrt($na) * sqrt($nb));
    }

    /**
     * Trả về top-k tài liệu gần nhất kèm điểm tương đồng.
     * @return array<int, array{doc: array, score: float}>
     */
    public static function search(string $query, array $index, int $k = 5): array
    {
        $qVec = self::vectorize($query, $index['idf']);
        $scored = [];
        foreach ($index['docs'] as $i => $doc) {
            $scored[] = ['doc' => $doc, 'score' => self::cosine($qVec, $index['vectors'][$i])];
        }
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($scored, 0, $k);
    }
}
