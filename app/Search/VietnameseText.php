<?php

namespace App\Search;

use Normalizer;

/** Text helpers for the lexical scorer (port of the helpers in js/scorer.js). */
final class VietnameseText
{
    /** NFC + lowercase; with $plain also strips diacritics (and maps đ to d). */
    public static function fold(string $s, bool $plain): string
    {
        $s = mb_strtolower(self::normalize($s, Normalizer::FORM_C), 'UTF-8');
        if ($plain) {
            $s = self::normalize($s, Normalizer::FORM_D);
            $s = (string) preg_replace('/[\x{0300}-\x{036f}]/u', '', $s);
            $s = str_replace('đ', 'd', $s);
        }

        return $s;
    }

    /** @return list<string> */
    public static function split(string $s): array
    {
        // Invalid UTF-8 makes preg fail; treat it as "no tokens".
        if (preg_match_all('/[\p{L}\p{N}%]+/u', $s, $m) === false) {
            return [];
        }

        return $m[0];
    }

    public static function hasDiacritics(string $s): bool
    {
        return preg_match('/[^\x00-\x7f]/u', self::normalize($s, Normalizer::FORM_C)) === 1;
    }

    /** Levenshtein distance on code points; returns $max + 1 as soon as it is known to exceed $max. */
    public static function lev(string $a, string $b, int $max): int
    {
        $la = mb_strlen($a, 'UTF-8');
        $lb = mb_strlen($b, 'UTF-8');
        if (abs($la - $lb) > $max) {
            return $max + 1;
        }
        $x = mb_str_split($a, 1, 'UTF-8');
        $y = mb_str_split($b, 1, 'UTF-8');
        $prev = range(0, $lb);
        for ($i = 1; $i <= $la; $i++) {
            $cur = [$i];
            for ($j = 1; $j <= $lb; $j++) {
                $cur[$j] = min($prev[$j] + 1, $cur[$j - 1] + 1, $prev[$j - 1] + ($x[$i - 1] === $y[$j - 1] ? 0 : 1));
            }
            $prev = $cur;
        }

        return $prev[$lb];
    }

    private static function normalize(string $s, int $form): string
    {
        $n = Normalizer::normalize($s, $form);

        return $n === false ? $s : $n;
    }
}
