<?php

namespace Tests\Unit\Search;

use App\Search\VietnameseText;
use PHPUnit\Framework\TestCase;

class VietnameseTextTest extends TestCase
{
    public function test_fold_keeps_or_strips_diacritics(): void
    {
        $this->assertSame('đánh bạc', VietnameseText::fold('Đánh Bạc', false));
        $this->assertSame('danh bac', VietnameseText::fold('Đánh Bạc', true));
    }

    public function test_fold_normalises_nfd_input(): void
    {
        $nfd = \Normalizer::normalize('Đánh Bạc', \Normalizer::FORM_D);
        $this->assertSame('đánh bạc', VietnameseText::fold($nfd, false));
    }

    public function test_split_keeps_letters_digits_and_percent(): void
    {
        $this->assertSame(['20%', 'đánh', 'bạc'], VietnameseText::split('20% đánh-bạc!'));
    }

    public function test_split_returns_empty_for_invalid_utf8(): void
    {
        $this->assertSame([], VietnameseText::split("\xFF"));
    }

    public function test_has_diacritics(): void
    {
        $this->assertFalse(VietnameseText::hasDiacritics('toi'));
        $this->assertTrue(VietnameseText::hasDiacritics('tôi'));
    }

    public function test_lev_counts_code_points_not_bytes(): void
    {
        $this->assertSame(2, VietnameseText::lev('đánh', 'danh', 2));
        $this->assertSame(3, VietnameseText::lev('abcdef', 'abc', 2));
        $this->assertSame(0, VietnameseText::lev('bạc', 'bạc', 1));
    }
}
