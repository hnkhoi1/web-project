<?php

namespace App\Search;

/** Builds a structured answer for a free-text legal question (port of Law.answer in legacy-final:js/law.js, without debug). */
class LawAnswerer
{
    private const DISCLAIMER = '(Tham khảo từ dữ liệu demo rút gọn, không thay thế tư vấn của luật sư.)';

    public function __construct(
        private readonly RankingService $ranking,
        private readonly ClauseTfIdf $tfidf,
    ) {}

    /**
     * @return array<string, mixed> {found: false} or the answer in 'overview' / 'specific' mode.
     */
    public function answer(string $question): array
    {
        $r = $this->ranking->rank($question);
        if ($r->status === 'none') {
            return ['found' => false];
        }
        $top = $r->results[0];
        $a = $top->article;
        $khoan = $a['khoan'];
        $sl = $this->extractSoLieu($question);
        $alt = $r->status === 'ambiguous'
            ? ['id' => $r->results[1]->article['id'], 'tieu_de' => $r->results[1]->article['tieu_de']]
            : null;
        $quant = $this->any($khoan, fn (array $k): bool => $k['ti_le_ton_thuong_min'] !== null
            || $k['gia_tri_tai_san_min'] !== null || $k['so_nguoi_chet_min'] !== null);
        $noFig = $sl['ti_le'] === null && $sl['gia_tri'] === null && $sl['so_nguoi_chet'] === null;
        $hasFollowUp = $this->any($khoan, fn (array $k): bool => $this->truthy($k['cau_hoi_lam_ro']));
        $picked = $noFig ? null : $this->pickKhoan($a, $question, $sl);

        $ask = null;
        foreach ($khoan as $k) {
            if ($this->truthy($k['cau_hoi_lam_ro'])) {
                $ask = $k['cau_hoi_lam_ro'];
                break;
            }
        }
        $ask ??= $this->any($khoan, fn (array $k): bool => $k['ti_le_ton_thuong_min'] !== null)
            ? 'Bạn cho mình biết tỉ lệ tổn thương cơ thể (%, theo giám định) hoặc mức độ thương tích để mình nêu đúng khoản và quyền lợi nhé.'
            : 'Bạn cho mình biết giá trị tài sản (triệu đồng) hoặc hậu quả cụ thể (số người chết...) để mình nêu đúng khoản nhé.';

        $base = [
            'found' => true,
            'score' => $top->score,
            'role' => $r->role,
            'alt' => $alt,
            'article' => ['id' => $a['id'], 'tieu_de' => $a['tieu_de'], 'chuong' => $a['chuong']],
        ];

        $needsOverview = ($quant && $noFig && preg_match('/(vô ý|lỡ tay|say rượu|hung khí)/iu', $question) !== 1)
            || ($hasFollowUp && $noFig)
            || (! $noFig && $picked === null);
        if ($needsOverview && count($khoan) > 1) {
            $khoanAll = array_map(fn (array $k): array => [
                'so_khoan' => $k['so_khoan'],
                'dieu_kien' => $k['dieu_kien'],
                'noi_dung' => $k['noi_dung'],
                'cau_hoi_lam_ro' => $this->truthy($k['cau_hoi_lam_ro']) ? $k['cau_hoi_lam_ro'] : null,
            ], $khoan);
            $text = "Tình huống: {$question}\nĐiều {$a['id']}. {$a['tieu_de']}\n\n"
                .implode("\n", array_map(fn (array $k): string => "Khoản {$k['so_khoan']} ({$k['dieu_kien']}): {$k['noi_dung']}", $khoanAll))
                ."\n\n{$ask}\n\n".self::DISCLAIMER;

            return $base + [
                'mode' => 'overview',
                'khoan_all' => $khoanAll,
                'ask' => $ask,
                'ghi_chu_quyen_loi' => $this->orNull($a['ghi_chu_quyen_loi']),
                'can_cu' => $this->orNull($a['can_cu_boi_thuong']),
                'note_title' => mb_substr("Điều {$a['id']} — {$a['tieu_de']}", 0, 120),
                'note_text' => mb_substr($text, 0, 5000),
            ];
        }

        $k = $picked ?? $this->pickKhoan($a, $question, $sl);
        $quyenLoi = $this->orNull($k['quyen_loi']);
        $ghiChu = $quyenLoi !== null ? null : $this->orNull($a['ghi_chu_quyen_loi']);
        $canCu = $quyenLoi !== null ? $this->orNull($a['can_cu_boi_thuong']) : null;
        $text = "Tình huống: {$question}\nĐiều {$a['id']}. {$a['tieu_de']} — Khoản {$k['so_khoan']} ({$k['dieu_kien']})\n\nMức xử lý với người vi phạm: {$k['noi_dung']}";
        if ($quyenLoi !== null) {
            $text .= "\n\nQuyền lợi của người bị hại: {$quyenLoi}".($canCu !== null ? " ({$canCu})" : '');
        } elseif ($ghiChu !== null) {
            $text .= "\n\nVề quyền lợi/bồi thường: {$ghiChu}";
        }
        $text .= "\n\n".self::DISCLAIMER;

        return $base + [
            'mode' => 'specific',
            'khoan' => ['so_khoan' => $k['so_khoan'], 'dieu_kien' => $k['dieu_kien'], 'noi_dung' => $k['noi_dung']],
            'quyen_loi' => $quyenLoi,
            'can_cu' => $canCu,
            'ghi_chu_quyen_loi' => $ghiChu,
            'note_title' => mb_substr("Điều {$a['id']}, Khoản {$k['so_khoan']} — {$a['tieu_de']}", 0, 120),
            'note_text' => mb_substr($text, 0, 5000),
        ];
    }

    /** @return array{ti_le: int|null, gia_tri: float|null, so_nguoi_chet: int|null} */
    public function extractSoLieu(string $text): array
    {
        $t = mb_strtolower($text, 'UTF-8');
        $tiLe = $giaTri = $soNguoiChet = null;
        if (preg_match('/([0-9]{1,3})\s*%/u', $t, $m) === 1) {
            $tiLe = (int) $m[1];
        }
        if (preg_match('/([0-9]+(?:[.,][0-9]+)?)\s*t(?:ỷ|ỉ|y)(?!\p{L})/u', $t, $m) === 1) {
            $giaTri = (float) str_replace(',', '.', $m[1]) * 1000;
        } elseif (preg_match('/([0-9]+(?:[.,][0-9]+)?)\s*tri[eệ]u/u', $t, $m) === 1) {
            $giaTri = (float) str_replace(',', '.', $m[1]);
        }
        if (preg_match('/ch[eế]t\s*([0-9]+)\s*ng[uư][oơ]?i/u', $t, $m) === 1
            || preg_match('/([0-9]+)\s*ng[uư][oơ]?i\s*ch[eế]t/u', $t, $m) === 1) {
            $soNguoiChet = (int) $m[1];
        }

        return ['ti_le' => $tiLe, 'gia_tri' => $giaTri, 'so_nguoi_chet' => $soNguoiChet];
    }

    /**
     * @param  array<string, mixed>  $article
     * @param  array{ti_le: int|null, gia_tri: float|null, so_nguoi_chet: int|null}  $sl
     * @return array<string, mixed>|null
     */
    public function pickKhoan(array $article, string $question, array $sl): ?array
    {
        $khoan = $article['khoan'];
        if ($sl['ti_le'] !== null || $sl['gia_tri'] !== null || $sl['so_nguoi_chet'] !== null) {
            $matches = [];
            foreach ($khoan as $k) {
                $any = false;
                $ok = true;
                foreach ([
                    [$sl['ti_le'], 'ti_le_ton_thuong'],
                    [$sl['gia_tri'], 'gia_tri_tai_san'],
                    [$sl['so_nguoi_chet'], 'so_nguoi_chet'],
                ] as [$v, $col]) {
                    if ($v !== null && ($k[$col.'_min'] !== null || $k[$col.'_max'] !== null)) {
                        $any = true;
                        if (! $this->inRange($v, $k[$col.'_min'], $k[$col.'_max'])) {
                            $ok = false;
                        }
                    }
                }
                if ($any && $ok) {
                    $matches[] = $k;
                }
            }
            if ($matches) {
                usort($matches, fn (array $a, array $b): int => $this->span($a) <=> $this->span($b));

                return $matches[0];
            }

            return null; // Do not guess a clause when the stated figures match nothing.
        }

        $docs = [];
        foreach ($khoan as $i => $k) {
            $docs[] = ['id' => $i, 'text' => $this->js($k['tu_khoa']).' '.$this->js($k['dieu_kien']).' '.$this->js($k['noi_dung'])];
        }
        $r = $this->tfidf->search($question, $this->tfidf->buildIndex($docs), 1);

        return $r ? $khoan[$r[0]['doc']['id']] : ($khoan[0] ?? null);
    }

    private function inRange(int|float $v, mixed $min, mixed $max): bool
    {
        return ($min === null || $v >= (float) $min) && ($max === null || $v <= (float) $max);
    }

    /** @param  array<string, mixed>  $k */
    private function span(array $k): float
    {
        $s = 0.0;
        foreach (['ti_le_ton_thuong', 'gia_tri_tai_san'] as $col) {
            $mn = $k[$col.'_min'];
            $mx = $k[$col.'_max'];
            if ($mn !== null || $mx !== null) {
                $s += $mn === null || $mx === null ? 1e6 : (float) $mx - (float) $mn;
            }
        }

        return $s;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  callable(array<string, mixed>): bool  $fn
     */
    private function any(array $items, callable $fn): bool
    {
        foreach ($items as $i) {
            if ($fn($i)) {
                return true;
            }
        }

        return false;
    }

    /** JS truthiness of a nullable string. */
    private function truthy(mixed $v): bool
    {
        return $v !== null && $v !== '';
    }

    /** JS `x || null` for strings. */
    private function orNull(mixed $v): ?string
    {
        return $this->truthy($v) ? (string) $v : null;
    }

    /** JS template-literal coercion of a nullable column. */
    private function js(mixed $v): string
    {
        return $v === null ? 'null' : (string) $v;
    }
}
