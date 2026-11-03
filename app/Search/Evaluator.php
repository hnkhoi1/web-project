<?php

namespace App\Search;

use InvalidArgumentException;

/**
 * Scores a ranking engine against the evaluation set (tests/fixtures/search-eval.json).
 *
 * Top-1 counts only the expected article. Top-3 also accepts any listed alternate.
 * A not-found case is correct, in both metrics, only when the status is "none",
 * and a "none" status is wrong on every other case whatever results it carries.
 */
class Evaluator
{
    public const ENGINES = ['lexical', 'semantic', 'blend'];

    public const SPLITS = ['tune', 'holdout', 'all'];

    /** @var list<array{id: string, question: string, expected: int|null, alternates: list<int>, clause: int|null, category: string, split: string}> */
    private array $cases;

    /**
     * @param  array<string, callable(string): RankResult>  $rankers  Ranker for each available engine.
     * @param  list<array<string, mixed>>|null  $cases  Defaults to the evaluation set.
     */
    public function __construct(private readonly array $rankers, ?array $cases = null)
    {
        $this->cases = $cases ?? json_decode(
            (string) file_get_contents(base_path('tests/fixtures/search-eval.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @return array{top1: float, top3: float, total: int, failures: list<array<string, mixed>>}
     *
     * @throws InvalidArgumentException For an engine without a ranker or an unknown split.
     */
    public function run(string $engine, string $split): array
    {
        $ranker = $this->rankerFor($engine);

        if (! in_array($split, self::SPLITS, true)) {
            throw new InvalidArgumentException("Unknown split \"{$split}\". Use one of: ".implode(', ', self::SPLITS).'.');
        }

        $total = $top1 = $top3 = 0;
        $failures = [];

        foreach ($this->cases as $case) {
            if ($split !== 'all' && $case['split'] !== $split) {
                continue;
            }

            $result = $ranker($case['question']);
            [$top1Correct, $top3Correct] = $this->score($case, $result);

            $total++;
            $top1 += (int) $top1Correct;
            $top3 += (int) $top3Correct;

            if (! $top1Correct || ! $top3Correct) {
                $failures[] = [
                    'id' => $case['id'],
                    'category' => $case['category'],
                    'question' => $case['question'],
                    'expected' => $case['expected'],
                    'alternates' => $case['alternates'],
                    'status' => $result->status,
                    'top3' => $this->topIds($result),
                    'top1_correct' => $top1Correct,
                    'top3_correct' => $top3Correct,
                ];
            }
        }

        return [
            'top1' => $total === 0 ? 0.0 : (float) ($top1 / $total),
            'top3' => $total === 0 ? 0.0 : (float) ($top3 / $total),
            'total' => $total,
            'failures' => $failures,
        ];
    }

    /**
     * @return callable(string): RankResult
     */
    private function rankerFor(string $engine): callable
    {
        if (! in_array($engine, self::ENGINES, true)) {
            throw new InvalidArgumentException("Unknown engine \"{$engine}\". Use one of: ".implode(', ', self::ENGINES).'.');
        }

        return $this->rankers[$engine]
            ?? throw new InvalidArgumentException("Engine \"{$engine}\" is not available yet.");
    }

    /**
     * @param  array{expected: int|null, alternates: list<int>}  $case
     * @return array{bool, bool} Top-1 correct, top-3 correct.
     */
    private function score(array $case, RankResult $result): array
    {
        if ($case['expected'] === null) {
            $correct = $result->status === 'none';

            return [$correct, $correct];
        }

        if ($result->status === 'none') {
            return [false, false];
        }

        $top3 = $this->topIds($result);

        return [
            ($top3[0] ?? null) === $case['expected'],
            array_intersect([$case['expected'], ...$case['alternates']], $top3) !== [],
        ];
    }

    /**
     * @return list<int>
     */
    private function topIds(RankResult $result): array
    {
        return array_map(fn (RankedArticle $article) => $article->id, array_slice($result->results, 0, 3));
    }
}
