<?php

namespace Tests\Unit\Search;

use Normalizer;
use PHPUnit\Framework\TestCase;

class EvalSetTest extends TestCase
{
    private const CATEGORIES = ['everyday', 'no_diacritics', 'typo', 'slang', 'rambling', 'two_crimes', 'not_found'];

    private const FIXTURES = __DIR__.'/../../fixtures';

    /** The set is frozen: the lexical baseline was measured against exactly this file. */
    private const EVAL_SHA256 = '6cd31b99ede5fcc2927567c2960d24d51b7836692a3dbe6209f93f152ce4bfa3';

    public function test_has_at_least_150_cases_with_the_expected_fields(): void
    {
        $cases = $this->cases();

        $this->assertGreaterThanOrEqual(150, count($cases));

        foreach ($cases as $case) {
            $this->assertSame(
                ['alternates', 'category', 'clause', 'expected', 'id', 'question', 'split'],
                $this->sortedKeys($case),
            );
            $this->assertContains($case['category'], self::CATEGORIES, $case['id']);
            $this->assertContains($case['split'], ['tune', 'holdout'], $case['id']);
        }
    }

    public function test_ids_are_unique(): void
    {
        $ids = array_column($this->cases(), 'id');

        $this->assertSame(array_unique($ids), $ids);
    }

    public function test_questions_are_unique_even_ignoring_case_diacritics_and_spacing(): void
    {
        $questions = array_map($this->normalize(...), array_column($this->cases(), 'question'));

        $this->assertSame([], array_values(array_unique(array_diff_assoc($questions, array_unique($questions)))));
    }

    public function test_every_label_refers_to_a_real_article_and_clause(): void
    {
        $clauses = $this->clausesByArticle();

        $this->assertCount(18, $clauses);

        foreach ($this->cases() as $case) {
            if ($case['expected'] !== null) {
                $this->assertArrayHasKey($case['expected'], $clauses, $case['id']);
                $this->assertNotContains($case['expected'], $case['alternates'], $case['id']);
            }
            foreach ($case['alternates'] as $alternate) {
                $this->assertArrayHasKey($alternate, $clauses, $case['id']);
            }
            if ($case['clause'] !== null) {
                $this->assertNotNull($case['expected'], $case['id']);
                $this->assertContains($case['clause'], $clauses[$case['expected']], $case['id']);
            }
        }
    }

    public function test_every_category_has_at_least_10_cases(): void
    {
        $counts = array_count_values(array_column($this->cases(), 'category'));

        foreach (self::CATEGORIES as $category) {
            $this->assertGreaterThanOrEqual(10, $counts[$category] ?? 0, $category);
        }
    }

    public function test_every_article_is_expected_in_at_least_4_cases(): void
    {
        $expected = array_filter(array_column($this->cases(), 'expected'), fn ($id) => $id !== null);
        $counts = array_count_values($expected);

        foreach (array_keys($this->clausesByArticle()) as $id) {
            $this->assertGreaterThanOrEqual(4, $counts[$id] ?? 0, "article {$id}");
        }
    }

    public function test_labels_fit_their_category(): void
    {
        foreach ($this->cases() as $case) {
            $isNotFound = $case['category'] === 'not_found';

            $this->assertSame($isNotFound, $case['expected'] === null, $case['id']);

            if ($isNotFound) {
                $this->assertSame([], $case['alternates'], $case['id']);
                $this->assertNull($case['clause'], $case['id']);
            }
            if ($case['category'] === 'two_crimes') {
                $this->assertNotSame([], $case['alternates'], $case['id']);
            }
            if ($case['category'] === 'no_diacritics') {
                $this->assertSame(1, preg_match('/^[\x20-\x7E]+$/', $case['question']), $case['id']);
            }
            if ($case['category'] === 'rambling') {
                $this->assertGreaterThanOrEqual(30, str_word_count($this->normalize($case['question'])), $case['id']);
            }
        }
    }

    public function test_every_third_case_of_each_category_is_held_out(): void
    {
        $positions = [];

        foreach ($this->cases() as $case) {
            $position = $positions[$case['category']] = ($positions[$case['category']] ?? 0) + 1;

            $this->assertSame($position % 3 === 0 ? 'holdout' : 'tune', $case['split'], $case['id']);
        }
    }

    public function test_holdout_is_about_a_third_and_covers_every_category(): void
    {
        $cases = $this->cases();
        $holdout = array_filter($cases, fn (array $case) => $case['split'] === 'holdout');
        $share = count($holdout) / count($cases);

        $this->assertGreaterThanOrEqual(0.30, $share);
        $this->assertLessThanOrEqual(0.36, $share);

        $covered = array_values(array_unique(array_column($holdout, 'category')));
        $this->assertEqualsCanonicalizing(self::CATEGORIES, $covered);
    }

    public function test_no_question_is_copied_from_the_scorer_golden_file(): void
    {
        $golden = json_decode((string) file_get_contents(self::FIXTURES.'/scorer-golden.json'), true);

        $this->assertIsArray($golden);
        $this->assertNotEmpty($golden);

        $goldenQuestions = array_map(fn (array $row) => $this->normalize($row['question']), $golden);
        $questions = array_map($this->normalize(...), array_column($this->cases(), 'question'));

        $this->assertSame([], array_values(array_intersect($questions, $goldenQuestions)));
    }

    public function test_comparison_catches_trivial_variants(): void
    {
        $this->assertSame('toi bi danh do', $this->normalize("  Tôi   BỊ\tđánh  Đỏ "));
    }

    public function test_the_set_has_not_changed_since_the_baseline_was_measured(): void
    {
        $this->assertSame(self::EVAL_SHA256, hash_file('sha256', self::FIXTURES.'/search-eval.json'));
    }

    public function test_the_baseline_records_lexical_holdout_scores_for_this_set(): void
    {
        $baseline = json_decode((string) file_get_contents(self::FIXTURES.'/search-baseline.json'), true);
        $holdout = array_filter($this->cases(), fn (array $case) => $case['split'] === 'holdout');

        $this->assertIsArray($baseline, 'search-baseline.json must exist');
        $this->assertSame('lexical', $baseline['engine']);
        $this->assertSame('holdout', $baseline['split']);
        $this->assertSame(count($holdout), $baseline['total']);
        $this->assertIsFloat($baseline['top1']);
        $this->assertIsFloat($baseline['top3']);
        $this->assertSame(self::EVAL_SHA256, $baseline['eval_sha256']);
    }

    /**
     * @return list<array{id: string, question: string, expected: int|null, alternates: list<int>, clause: int|null, category: string, split: string}>
     */
    private function cases(): array
    {
        $cases = json_decode((string) file_get_contents(self::FIXTURES.'/search-eval.json'), true);

        $this->assertIsArray($cases, 'search-eval.json must be a JSON list');

        return $cases;
    }

    /**
     * @return array<int, list<int>> Clause numbers by article id.
     */
    private function clausesByArticle(): array
    {
        /** @var list<array{id: int, khoan: list<array{so_khoan: int}>}> $articles */
        $articles = require __DIR__.'/../../../database/data/dieu_luat_data.php';

        $clauses = [];
        foreach ($articles as $article) {
            $clauses[$article['id']] = array_column($article['khoan'], 'so_khoan');
        }

        return $clauses;
    }

    /**
     * @param  array<string, mixed>  $case
     * @return list<string>
     */
    private function sortedKeys(array $case): array
    {
        $keys = array_keys($case);
        sort($keys);

        return $keys;
    }

    private function normalize(string $question): string
    {
        $decomposed = (string) Normalizer::normalize(mb_strtolower($question), Normalizer::FORM_D);
        $plain = str_replace('đ', 'd', (string) preg_replace('/\p{Mn}+/u', '', $decomposed));

        return trim((string) preg_replace('/\s+/u', ' ', $plain));
    }
}
