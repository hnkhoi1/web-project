<?php

namespace App\Search;

/**
 * Lexical question-to-article scorer: 0.60 concept + 0.30 BM25 keyword + 0.10 fuzzy (typos).
 * Behaviour-identical port of js/scorer.js (golden-tested); the concept dictionary lives in data/concepts.json.
 */
final class LexicalScorer
{
    private const W_CONCEPT = 0.60;

    private const W_LEXICAL = 0.30;

    private const W_FUZZY = 0.10;

    private const MIN_SCORE = 0.20;

    /** Homographs without diacritics (đá/da, đập/đạp/dap): only recognised in diacritic mode. */
    private const DIAC_ONLY = ['đá', 'đập', 'đạp'];

    private const STOP_RAW = ['tôi', 'mình', 'em', 'anh', 'chị', 'bị', 'được', 'là', 'và', 'có', 'không', 'cho', 'của', 'thì', 'mà',
        'đã', 'đang', 'vừa', 'rồi', 'một', 'những', 'các', 'này', 'khi', 'ở', 'với', 'để', 'về', 'gì', 'nào', 'sao', 'thế',
        'nên', 'hay', 'ạ', 'nhé', 'ơi', 'xin', 'hỏi', 'cần', 'muốn', 'bởi', 'vì', 'do', 'nếu', 'như', 'bao', 'nhiêu', 'phải',
        'làm', 'ra', 'vào', 'lại', 'cũng', 'đó', 'người', 'ta', 'thì', 'bên', 'trong', 'tại', 'bằng', 'từ', 'đến', 'sẽ'];

    /** @var list<array{id: string, laws: array<int, int|float>, p: list<string>}>|null */
    private ?array $concepts = null;

    /** @var array<string, array{map: array<string, array{cid: string, laws: array<int, int|float>}>, maxLen: int, stop: array<string, true>, singles: list<string>}> */
    private array $lexicons = [];

    /** @var array<string, array{docs: list<array<string, mixed>>, df: array<string, int>, avg: float, n: int}> */
    private array $indexes = [];

    /** @var list<array<string, mixed>>|null */
    private ?array $indexedData = null;

    /**
     * @param  list<array<string, mixed>>  $articles
     */
    public function rank(string $query, array $articles, float $minScore = self::MIN_SCORE, float $margin = 0.10): RankResult
    {
        $plain = ! VietnameseText::hasDiacritics($query);
        $lex = $this->lexicon($plain);
        $idx = $this->index($articles, $plain);
        $seg = $this->segment($query, $plain);

        // Typos: an unrecognised single word within 1-2 edits of a dictionary single word counts as a hit, at 70% weight.
        foreach ($seg as &$s) {
            $t = $s['t'];
            if (isset($s['cid']) || isset($lex['stop'][$t]) || mb_strlen($t) < 4 || $this->startsWithDigit($t)) {
                continue;
            }
            $max = mb_strlen($t) >= 7 ? 2 : 1;
            $best = null;
            $bd = 9;
            $first = mb_substr($t, 0, 1);
            foreach ($lex['singles'] as $k) {
                if (mb_substr($k, 0, 1) !== $first) {
                    continue;
                }
                $d = VietnameseText::lev($t, $k, $max);
                if ($d <= $max && $d < $bd) {
                    $bd = $d;
                    $best = $k;
                }
            }
            if ($best !== null) {
                $e = $lex['map'][$best];
                $scaled = [];
                foreach ($e['laws'] as $n => $w) {
                    $scaled[$n] = $w * 0.7;
                }
                $s['cid'] = $e['cid'].'~';
                $s['laws'] = $scaled;
                $s['phrase'] = $t.'≈'.$best;
            }
        }
        unset($s);

        // Concepts: gather hits per concept id.
        $concepts = [];
        foreach ($seg as $s) {
            if (! isset($s['cid'], $s['laws'], $s['phrase'])) {
                continue;
            }
            $concepts[$s['cid']] ??= ['laws' => $s['laws'], 'hits' => []];
            $concepts[$s['cid']]['hits'][] = $s['phrase'];
        }

        // Keywords.
        $qTerms = [];
        $leftovers = [];
        foreach ($seg as $s) {
            $t = $s['t'];
            if (isset($lex['stop'][$t]) || $this->startsWithDigit($t)) {
                continue;
            }
            if (! in_array($t, $qTerms, true)) {
                $qTerms[] = $t;
            }
            if (! isset($s['cid']) && mb_strlen($t) >= 4) {
                $leftovers[] = $t;
            }
        }

        $results = [];
        foreach ($idx['docs'] as $doc) {
            $raw = 0.0;
            $why = [];
            foreach ($concepts as $c) {
                $w = $c['laws'][$doc['key']] ?? 0;
                if (! $w) {
                    continue;
                }
                $hits = count($c['hits']);
                $v = $w * (1 + 0.25 * ($hits - 1));
                $raw += $v;
                $why[] = $c['hits'][0].($hits > 1 ? ' (+'.($hits - 1).')' : '').' → '.number_format($v, 2, '.', '');
            }
            $concept = $raw > 0 ? 1 - exp(-1.2 * $raw) : 0.0;
            $s = $this->bm25($idx, $doc, $qTerms);
            $lexical = $s / ($s + 3);
            $fz = 0;
            foreach ($leftovers as $t) {
                if (! empty($doc['tf'][$t])) {
                    continue;
                }
                $max = mb_strlen($t) >= 7 ? 2 : 1;
                foreach ($doc['vocab'] as $term) {
                    if (VietnameseText::lev($t, (string) $term, $max) <= $max) {
                        $fz++;
                        break;
                    }
                }
            }
            $fuzzy = $leftovers ? min(1.0, $fz / count($leftovers)) : 0.0;
            $score = self::W_CONCEPT * $concept + self::W_LEXICAL * $lexical + self::W_FUZZY * $fuzzy;
            $results[] = new RankedArticle(
                id: $doc['id'],
                article: $doc['article'],
                score: (float) $score,
                parts: ['concept' => (float) $concept, 'lexical' => (float) $lexical, 'fuzzy' => (float) $fuzzy],
                why: $why,
            );
        }
        usort($results, fn (RankedArticle $a, RankedArticle $b): int => $b->score <=> $a->score);

        $top = $results[0] ?? null;
        $second = $results[1] ?? null;
        $status = 'ok';

        // A specific concept must not fall back to a related article when its strongest target is not loaded.
        // Only the high-risk theft (173), trust-abuse (175) and defamation (156) targets are guarded.
        $missingTarget = null;
        foreach ($concepts as $c) {
            $laws = $c['laws'];
            ksort($laws);
            $targets = array_keys($laws);
            if (! $targets) {
                continue;
            }
            usort($targets, fn (int $a, int $b): int => $laws[$b] <=> $laws[$a]);
            $target = $targets[0];
            $present = false;
            foreach ($idx['docs'] as $doc) {
                if ($doc['key'] === (string) $target) {
                    $present = true;
                    break;
                }
            }
            if (! $present && $laws[$target] >= 0.8 && in_array($target, [156, 173, 175], true)) {
                $missingTarget = $target;
                break;
            }
        }
        if ($top === null || $top->score < $minScore) {
            $status = 'none';
        } elseif ($second !== null && $second->score >= self::MIN_SCORE && $top->score - $second->score < $margin) {
            $status = 'ambiguous';
        }
        if ($missingTarget !== null) {
            $status = 'none';
        }

        return new RankResult(
            query: $query,
            mode: $plain ? 'khong_dau' : 'co_dau',
            role: self::detectRole($query),
            status: $status,
            results: $results,
            concepts: array_map('strval', array_keys($concepts)),
            missingTarget: $missingTarget,
            engine: 'lexical',
        );
    }

    /** Who is asking: nguoi_vi_pham (offender), nan_nhan (victim) or chua_ro (unclear). */
    public static function detectRole(string $query): string
    {
        $s = VietnameseText::fold($query, false);
        if (preg_match('/bị\s+(bắt|phạt|khởi tố|truy tố|tạm giữ|triệu tập|công an|kiện)/u', $s)) {
            return 'nguoi_vi_pham';
        }
        if (preg_match('/(tôi|mình|em|con tôi|chồng tôi|vợ tôi|bạn tôi)\s+(đã\s+|vừa\s+|lỡ\s+|có\s+)?(đánh|giết|trộm|lấy|chửi|lừa|cướp|đâm|tông|chém|đấm|tát)/u', $s)) {
            return 'nguoi_vi_pham';
        }
        if (preg_match('/\bbị\b|nạn nhân|người nhà|gia đình tôi|con tôi bị|bố mẹ tôi/u', $s)) {
            return 'nan_nhan';
        }

        return 'chua_ro';
    }

    /**
     * @return list<array{id: string, laws: array<int, int|float>, p: list<string>}>
     */
    private function concepts(): array
    {
        if ($this->concepts === null) {
            $json = file_get_contents(__DIR__.'/data/concepts.json');
            $decoded = json_decode($json === false ? '[]' : $json, true, 512, JSON_THROW_ON_ERROR);
            /** @var list<array{id: string, laws: array<int, int|float>, p: list<string>}> $decoded */
            $this->concepts = $decoded;
        }

        return $this->concepts;
    }

    /**
     * @return array{map: array<string, array{cid: string, laws: array<int, int|float>}>, maxLen: int, stop: array<string, true>, singles: list<string>}
     */
    private function lexicon(bool $plain): array
    {
        $key = $plain ? 'p' : 'd';
        if (isset($this->lexicons[$key])) {
            return $this->lexicons[$key];
        }
        $map = [];
        $maxLen = 1;
        $stop = [];
        foreach (self::STOP_RAW as $w) {
            $stop[VietnameseText::fold($w, $plain)] = true;
        }
        foreach ($this->concepts() as $c) {
            foreach ($c['p'] as $ph) {
                if ($plain && in_array($ph, self::DIAC_ONLY, true)) {
                    continue;
                }
                $parts = VietnameseText::split(VietnameseText::fold($ph, $plain));
                $k = implode(' ', $parts);
                $map[$k] = ['cid' => $c['id'], 'laws' => $c['laws']];
                $maxLen = max($maxLen, count($parts));
            }
        }
        $singles = [];
        foreach (array_keys($map) as $k) {
            $k = (string) $k;
            if (! str_contains($k, ' ') && mb_strlen($k) >= 4) {
                $singles[] = $k;
            }
        }

        return $this->lexicons[$key] = ['map' => $map, 'maxLen' => $maxLen, 'stop' => $stop, 'singles' => $singles];
    }

    /**
     * Longest-phrase-first segmentation.
     *
     * @return list<array{t: string, phrase?: string, cid?: string, laws?: array<int, int|float>}>
     */
    private function segment(string $text, bool $plain): array
    {
        $lex = $this->lexicon($plain);
        $toks = VietnameseText::split(VietnameseText::fold($text, $plain));
        $total = count($toks);
        $out = [];
        $i = 0;
        while ($i < $total) {
            $hit = null;
            for ($n = min($lex['maxLen'], $total - $i); $n >= 1; $n--) {
                $key = implode(' ', array_slice($toks, $i, $n));
                if (isset($lex['map'][$key])) {
                    $hit = ['n' => $n, 'key' => $key, 'e' => $lex['map'][$key]];
                    break;
                }
            }
            if ($hit !== null) {
                $out[] = ['t' => str_replace(' ', '_', $hit['key']), 'phrase' => $hit['key'], 'cid' => $hit['e']['cid'], 'laws' => $hit['e']['laws']];
                $i += $hit['n'];
            } else {
                $out[] = ['t' => (string) $toks[$i]];
                $i++;
            }
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $articles
     * @return array{docs: list<array<string, mixed>>, df: array<string, int>, avg: float, n: int}
     */
    private function index(array $articles, bool $plain): array
    {
        if ($this->indexedData !== $articles) {
            $this->indexedData = $articles;
            $this->indexes = [];
        }
        $key = $plain ? 'p' : 'd';

        return $this->indexes[$key] ??= $this->buildIndex($articles, $plain);
    }

    /**
     * @param  list<array<string, mixed>>  $articles
     * @return array{docs: list<array<string, mixed>>, df: array<string, int>, avg: float, n: int}
     */
    private function buildIndex(array $articles, bool $plain): array
    {
        $lex = $this->lexicon($plain);
        $docs = [];
        $df = [];
        $total = 0;
        foreach ($articles as $a) {
            $khoan = [];
            foreach ((array) ($a['khoan'] ?? []) as $k) {
                $khoan[] = [$k['dieu_kien'] ?? null, $k['noi_dung'] ?? null, $k['tu_khoa'] ?? null];
            }
            $head = $this->toText([$a['tieu_de'] ?? null, $a['tieu_de'] ?? null, $a['tieu_de'] ?? null, $a['tu_khoa'] ?? null, $a['tu_khoa'] ?? null]);
            $body = $this->toText([$a['tom_tat'] ?? null, $khoan]);
            $tf = [];
            $len = 0;
            foreach ($this->segment($head.' '.$body, $plain) as $s) {
                if (isset($lex['stop'][$s['t']]) || $this->startsWithDigit($s['t'])) {
                    continue;
                }
                $tf[$s['t']] = ($tf[$s['t']] ?? 0) + 1;
                $len++;
            }
            foreach (array_keys($tf) as $t) {
                $df[$t] = ($df[$t] ?? 0) + 1;
            }
            $docs[] = [
                'id' => (int) $a['id'],
                'article' => $a,
                'key' => (string) $a['id'],
                'tf' => $tf,
                'len' => $len,
                'vocab' => array_keys($tf),
            ];
            $total += $len;
        }

        return ['docs' => $docs, 'df' => $df, 'avg' => $total / max(1, count($docs)), 'n' => count($docs)];
    }

    /**
     * @param  array{docs: list<array<string, mixed>>, df: array<string, int>, avg: float, n: int}  $idx
     * @param  array<string, mixed>  $doc
     * @param  list<string>  $qTerms
     */
    private function bm25(array $idx, array $doc, array $qTerms): float
    {
        $k1 = 1.2;
        $b = 0.75;
        $s = 0.0;
        foreach ($qTerms as $t) {
            $f = $doc['tf'][$t] ?? 0;
            if (! $f) {
                continue;
            }
            $df = $idx['df'][$t] ?? 0;
            $idf = log(1 + ($idx['n'] - $df + 0.5) / ($df + 0.5));
            $s += $idf * ($f * ($k1 + 1)) / ($f + $k1 * (1 - $b + $b * $doc['len'] / $idx['avg']));
        }

        return $s;
    }

    private function toText(mixed $x): string
    {
        if (is_array($x)) {
            return implode(' ', array_map(fn (mixed $v): string => $this->toText($v), $x));
        }

        return $x === null ? '' : (string) $x;
    }

    private function startsWithDigit(string $t): bool
    {
        return $t !== '' && $t[0] >= '0' && $t[0] <= '9';
    }
}
