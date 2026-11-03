<?php

namespace Tests\Feature\Search;

use App\Search\Evaluator;
use App\Search\RankedArticle;
use App\Search\RankResult;
use Database\Seeders\LawSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EvaluatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_crimes_top1_counts_only_expected_and_top3_counts_alternates(): void
    {
        $report = $this->evaluate(
            [
                $this->makeCase('expected-first', 'two_crimes', 168, [134]),
                $this->makeCase('alternate-first', 'two_crimes', 168, [134]),
                $this->makeCase('alternate-third', 'two_crimes', 168, [134]),
                $this->makeCase('neither', 'two_crimes', 168, [134]),
            ],
            [
                'expected-first' => $this->ranked('ok', [168, 134]),
                'alternate-first' => $this->ranked('ok', [134, 168]),
                'alternate-third' => $this->ranked('ok', [1, 2, 134]),
                'neither' => $this->ranked('ok', [1, 2, 3, 134]),
            ],
        );

        $this->assertSame(0.25, $report['top1']);
        $this->assertSame(0.75, $report['top3']);
        $this->assertSame(4, $report['total']);
    }

    public function test_not_found_is_correct_only_when_status_is_none_in_both_metrics(): void
    {
        $report = $this->evaluate(
            [
                $this->makeCase('none', 'not_found', null),
                $this->makeCase('ok', 'not_found', null),
                $this->makeCase('ambiguous', 'not_found', null),
                $this->makeCase('none-with-results', 'not_found', null),
            ],
            [
                'none' => $this->ranked('none', []),
                'ok' => $this->ranked('ok', [123]),
                'ambiguous' => $this->ranked('ambiguous', [123, 134]),
                'none-with-results' => $this->ranked('none', [123]),
            ],
        );

        $this->assertSame(0.5, $report['top1']);
        $this->assertSame(0.5, $report['top3']);
        $this->assertEqualsCanonicalizing(['ok', 'ambiguous'], array_column($report['failures'], 'id'));
    }

    public function test_regular_cases_score_top1_and_top3_separately_and_status_none_is_wrong(): void
    {
        $report = $this->evaluate(
            [
                $this->makeCase('first', 'everyday', 123),
                $this->makeCase('ambiguous-first', 'slang', 123),
                $this->makeCase('second', 'typo', 123),
                $this->makeCase('fourth', 'rambling', 123),
                $this->makeCase('none-status', 'no_diacritics', 123),
            ],
            [
                'first' => $this->ranked('ok', [123, 134]),
                'ambiguous-first' => $this->ranked('ambiguous', [123, 134]),
                'second' => $this->ranked('ok', [134, 123]),
                'fourth' => $this->ranked('ok', [1, 2, 3, 123]),
                'none-status' => $this->ranked('none', [123]),
            ],
        );

        $this->assertSame(0.4, $report['top1']);
        $this->assertSame(0.6, $report['top3']);
    }

    public function test_regular_alternates_count_for_top3_but_not_top1(): void
    {
        $report = $this->evaluate(
            [$this->makeCase('arguable', 'everyday', 135, [134])],
            ['arguable' => $this->ranked('ok', [134, 135])],
        );

        $this->assertSame(0.0, $report['top1']);
        $this->assertSame(1.0, $report['top3']);
    }

    public function test_failures_list_any_case_missing_either_metric_with_what_was_returned(): void
    {
        $report = $this->evaluate(
            [
                $this->makeCase('good', 'everyday', 123),
                $this->makeCase('top3-only', 'everyday', 123),
                $this->makeCase('miss', 'everyday', 123),
            ],
            [
                'good' => $this->ranked('ok', [123]),
                'top3-only' => $this->ranked('ambiguous', [134, 123, 135, 138]),
                'miss' => $this->ranked('none', []),
            ],
        );

        $this->assertSame(['top3-only', 'miss'], array_column($report['failures'], 'id'));

        $failure = $report['failures'][0];
        $this->assertSame('ambiguous', $failure['status']);
        $this->assertSame([134, 123, 135], $failure['top3']);
        $this->assertSame(123, $failure['expected']);
        $this->assertFalse($failure['top1_correct']);
        $this->assertTrue($failure['top3_correct']);
    }

    public function test_split_filters_cases_and_all_includes_every_case(): void
    {
        $cases = [
            $this->makeCase('tuned', 'everyday', 123, split: 'tune'),
            $this->makeCase('held', 'everyday', 123, split: 'holdout'),
        ];
        $rankings = ['tuned' => $this->ranked('ok', [1]), 'held' => $this->ranked('ok', [123])];

        $this->assertSame(1.0, $this->evaluate($cases, $rankings, 'holdout')['top1']);
        $this->assertSame(0.0, $this->evaluate($cases, $rankings, 'tune')['top1']);
        $this->assertSame(2, $this->evaluate($cases, $rankings, 'all')['total']);
    }

    public function test_unknown_engine_and_split_are_rejected(): void
    {
        $evaluator = new Evaluator(['lexical' => fn (string $question) => $this->ranked('none', [])], []);

        $this->assertThrows(fn () => $evaluator->run('semantic', 'all'), InvalidArgumentException::class);
        $this->assertThrows(fn () => $evaluator->run('lexical', 'nope'), InvalidArgumentException::class);
    }

    public function test_an_empty_split_scores_zero(): void
    {
        $report = $this->evaluate([], [], 'holdout');

        $this->assertSame(['top1' => 0.0, 'top3' => 0.0, 'total' => 0, 'failures' => []], $report);
    }

    public function test_command_prints_scores_and_failures_for_lexical(): void
    {
        $this->seed(LawSeeder::class);

        $this->artisan('search:eval', ['--engine' => 'lexical', '--split' => 'holdout'])
            ->expectsOutputToContain('top-1')
            ->expectsOutputToContain('top-3')
            ->assertSuccessful();
    }

    public function test_command_json_output_matches_the_evaluator_shape(): void
    {
        $this->seed(LawSeeder::class);

        $exitCode = $this->withoutMockingConsoleOutput()
            ->artisan('search:eval', ['--engine' => 'lexical', '--split' => 'holdout', '--json' => true]);

        $this->assertSame(0, $exitCode);

        $report = json_decode(Artisan::output(), true);

        $this->assertIsFloat($report['top1']);
        $this->assertIsFloat($report['top3']);
        $this->assertGreaterThan(0, $report['total']);
        $this->assertIsArray($report['failures']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unavailableEngines(): array
    {
        return ['semantic' => ['semantic'], 'blend' => ['blend']];
    }

    #[DataProvider('unavailableEngines')]
    public function test_command_exits_non_zero_for_engines_that_do_not_exist_yet(string $engine): void
    {
        $this->artisan('search:eval', ['--engine' => $engine])
            ->expectsOutputToContain("Engine \"{$engine}\" is not available yet")
            ->assertFailed();
    }

    public function test_command_rejects_an_unknown_split(): void
    {
        $this->artisan('search:eval', ['--engine' => 'lexical', '--split' => 'nope'])
            ->expectsOutputToContain('Unknown split')
            ->assertFailed();
    }

    /**
     * @param  list<array<string, mixed>>  $cases
     * @param  array<string, RankResult>  $rankings  Keyed by case id (the question equals the id in these tests).
     * @return array{top1: float, top3: float, total: int, failures: list<array<string, mixed>>}
     */
    private function evaluate(array $cases, array $rankings, string $split = 'all'): array
    {
        $ranker = fn (string $question): RankResult => $rankings[$question];

        return (new Evaluator(['lexical' => $ranker], $cases))->run('lexical', $split);
    }

    /**
     * @param  list<int>  $alternates
     * @return array<string, mixed>
     */
    private function makeCase(string $id, string $category, ?int $expected, array $alternates = [], string $split = 'tune'): array
    {
        return [
            'id' => $id,
            'question' => $id,
            'expected' => $expected,
            'alternates' => $alternates,
            'clause' => null,
            'category' => $category,
            'split' => $split,
        ];
    }

    /**
     * @param  list<int>  $ids
     */
    private function ranked(string $status, array $ids): RankResult
    {
        $results = array_map(
            fn (int $id) => new RankedArticle($id, [], 1.0, ['concept' => 0.0, 'lexical' => 0.0, 'fuzzy' => 0.0], []),
            $ids,
        );

        return new RankResult('q', 'co_dau', 'unknown', $status, $results, [], null);
    }
}
