<?php

namespace App\Console\Commands;

use App\Search\EmbeddingClient;
use App\Search\EmbeddingStore;
use App\Search\Evaluator;
use App\Search\RankedArticle;
use App\Search\RankingService;
use App\Search\RankResult;
use App\Search\RegressionSet;
use App\Search\SemanticScorer;
use Illuminate\Console\Command;

/**
 * Grid search for the blend settings on split=tune. Holdout questions are never loaded or embedded.
 *
 * Best top-1, then best top-3, among settings where every original question still passes. Grid order
 * breaks ties, smallest semantic weights first, so the dictionary keeps the most say.
 */
class SearchTune extends Command
{
    private const LEXICAL_WEIGHTS = [1.0, 0.9, 0.8, 0.7, 0.6, 0.5];

    private const SEMANTIC_WEIGHTS = [0.1, 0.15, 0.2, 0.3, 0.4, 0.5];

    /** Lower than the no-hit range, so a concept hit keeps the dictionary in charge. */
    private const CONCEPT_WEIGHTS = [0.0, 0.05, 0.1, 0.15, 0.2];

    /** e5-small puts nearly everything between 0.78 and 0.93, so only that band is worth searching. */
    private const COS_RANGES = [
        [0.75, 0.85], [0.75, 0.9], [0.78, 0.88], [0.78, 0.9], [0.8, 0.85],
        [0.8, 0.88], [0.8, 0.9], [0.82, 0.88], [0.82, 0.9], [0.84, 0.9],
    ];

    private const THRESHOLD_STEP = 0.01;

    /** Blended new-topic threshold, as a multiple of min_score: the lexical settings' 0.25 / 0.20. */
    private const NEW_TOPIC_RATIO = 1.25;

    protected $signature = 'search:tune';

    protected $description = 'Find the best blend settings on the tune split';

    /** @var list<array{lexical: RankResult, cosines: array<int, float>, expected: int|null, accepted: array<mixed>, missing: bool}> */
    private array $tune = [];

    /** @var list<array{lexical: RankResult, cosines: array<int, float>, expected: int|null}> */
    private array $regression = [];

    public function handle(RankingService $ranking, EmbeddingClient $client, EmbeddingStore $store, SemanticScorer $semantic): int
    {
        $vectors = $store->vectorsFor((string) config('search.embedder_model'));
        if ($vectors === []) {
            $this->error('No stored vectors. Run search:embed first.');

            return self::FAILURE;
        }

        $measure = function (string $question) use ($ranking, $client, $semantic, $vectors): ?array {
            $vector = $client->embedQuery($question);
            if ($vector === null) {
                $this->error("Embedder failed on: {$question}");

                return null;
            }

            return ['lexical' => $ranking->rankLexical($question), 'cosines' => $semantic->cosines($vector, $vectors)];
        };

        foreach ($this->tuneCases() as $case) {
            $measured = $measure($case['question']);
            if ($measured === null) {
                return self::FAILURE;
            }
            $this->tune[] = $measured + [
                'expected' => $case['expected'],
                'accepted' => [$case['expected'], ...$case['alternates']],
                'missing' => $measured['lexical']->missingTarget !== null,
            ];
        }

        $set = RegressionSet::load();
        foreach ([...$set->found(), ...array_fill_keys($set->none(), null)] as $question => $expected) {
            $measured = $measure((string) $question);
            if ($measured === null) {
                return self::FAILURE;
            }
            $this->regression[] = $measured + ['expected' => $expected];
        }

        $best = $this->search($ranking);
        if ($best === null) {
            $this->error('No setting in the grid keeps every original question passing.');

            return self::FAILURE;
        }

        $config = $best['config'];
        config(['search.blend' => array_merge((array) config('search.blend'), $config)]);
        $config['margin'] = $this->bestMargin($ranking, $best['config']);
        $config['search_min_score'] = $this->bestSearchThreshold($ranking, $config['min_score']);
        $config['new_topic_min_score'] = round($config['min_score'] * self::NEW_TOPIC_RATIO, 2);
        config(['search.blend' => $config]);

        $verified = $this->verify($ranking);
        if ($verified !== [$best['top1'], $best['top3']]) {
            $this->error('Evaluator disagrees with the grid: '.json_encode($verified));

            return self::FAILURE;
        }

        $total = count($this->tune);
        $this->line(sprintf('tune: top-1 %d/%d (%.4f), top-3 %d/%d (%.4f)', $best['top1'], $total, $best['top1'] / $total, $best['top3'], $total, $best['top3'] / $total));
        $this->line('settings tried: '.$best['tried'].', passing the original questions: '.$best['passing']);
        foreach ($config as $key => $value) {
            $this->line("{$key} = {$value}");
        }

        file_put_contents(storage_path('app/search-tune.json'), json_encode(
            ['top1' => $best['top1'] / $total, 'top3' => $best['top3'] / $total, 'total' => $total, 'blend' => $config],
            JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR,
        )."\n");
        $this->line('Written to '.storage_path('app/search-tune.json').'. Copy the values into config/search.php.');

        return self::SUCCESS;
    }

    /** @return list<array<string, mixed>> */
    private function tuneCases(): array
    {
        $cases = json_decode((string) file_get_contents(base_path('tests/fixtures/search-eval.json')), true, flags: JSON_THROW_ON_ERROR);

        return array_values(array_filter($cases, fn (array $case): bool => $case['split'] === 'tune'));
    }

    /** @return array{config: array<string, float>, top1: int, top3: int, tried: int, passing: int}|null */
    private function search(RankingService $ranking): ?array
    {
        $best = null;
        $tried = $passing = 0;

        foreach (self::SEMANTIC_WEIGHTS as $semantic) {
            foreach (self::CONCEPT_WEIGHTS as $concept) {
                foreach (self::LEXICAL_WEIGHTS as $lexical) {
                    if ($concept > $semantic || $lexical + $semantic > 1.0 + 1e-9) {
                        continue;
                    }
                    foreach (self::COS_RANGES as [$floor, $ceil]) {
                        $tried++;
                        $config = [
                            'lexical_weight' => $lexical,
                            'semantic_weight' => $semantic,
                            'semantic_weight_with_concept' => $concept,
                            'cos_floor' => $floor,
                            'cos_ceil' => $ceil,
                        ];
                        config(['search.blend' => $config + ['min_score' => 0.0, 'margin' => 0.0]]);

                        $found = $this->bestMinScore($ranking);
                        if ($found === null) {
                            continue;
                        }
                        $passing++;

                        if ($best === null || [$found['top1'], $found['top3']] > [$best['top1'], $best['top3']]) {
                            $best = $found + ['config' => $config + ['min_score' => $found['min_score']]];
                        }
                    }
                }
            }
        }

        return $best === null ? null : $best + ['tried' => $tried, 'passing' => $passing];
    }

    /**
     * min_score only decides "none", so it is swept on the rankings the current weights give. The middle
     * of the best thresholds is taken, keeping it as far as the tune split allows from flipping a case.
     *
     * @return array{top1: int, top3: int, min_score: float}|null Null when an original question fails at every threshold.
     */
    private function bestMinScore(RankingService $ranking): ?array
    {
        $lowest = 0.0;
        $highest = 1.0;
        foreach ($this->regression as $case) {
            $blended = $this->blendCase($ranking, $case);
            $top = $blended->results[0];
            if ($case['expected'] === null) {
                $lowest = max($lowest, $top->score);
            } elseif ($top->id !== $case['expected']) {
                return null;
            } else {
                $highest = min($highest, $top->score);
            }
        }

        $rows = [];
        foreach ($this->tune as $case) {
            $blended = $this->blendCase($ranking, $case);
            $ids = array_map(fn (RankedArticle $r): int => $r->id, array_slice($blended->results, 0, 3));
            $rows[] = [
                'score' => $blended->results[0]->score,
                'not_found' => $case['expected'] === null,
                'missing' => $case['missing'],
                'top1' => $ids[0] === $case['expected'],
                'top3' => array_intersect($case['accepted'], $ids) !== [],
            ];
        }

        $best = null;
        $ties = [];
        $steps = (int) round(1 / self::THRESHOLD_STEP);
        for ($i = 1; $i <= $steps; $i++) {
            $threshold = $i * self::THRESHOLD_STEP;
            if ($threshold <= $lowest || $threshold > $highest) {
                continue;
            }
            $top1 = $top3 = 0;
            foreach ($rows as $row) {
                $none = $row['missing'] || $row['score'] < $threshold;
                $top1 += (int) ($row['not_found'] ? $none : (! $none && $row['top1']));
                $top3 += (int) ($row['not_found'] ? $none : (! $none && $row['top3']));
            }
            if ($best === null || [$top1, $top3] > $best) {
                $best = [$top1, $top3];
                $ties = [];
            }
            if ([$top1, $top3] === $best) {
                $ties[] = round($threshold, 2);
            }
        }

        return $best === null ? null : ['top1' => $best[0], 'top3' => $best[1], 'min_score' => $ties[intdiv(count($ties), 2)]];
    }

    /**
     * The eval metrics ignore "ambiguous", so the margin cannot move them. It is chosen so that the status
     * tells the truth most often: "ok" on a right top article, "ambiguous" on a wrong one.
     *
     * @param  array<string, float>  $config
     */
    private function bestMargin(RankingService $ranking, array $config): float
    {
        $best = 0.0;
        $bestScore = -1;
        for ($i = 1; $i <= 15; $i++) {
            $margin = round($i * self::THRESHOLD_STEP, 2);
            config(['search.blend' => $config + ['margin' => $margin]]);
            $score = 0;
            foreach ($this->tune as $case) {
                $blended = $this->blendCase($ranking, $case);
                if ($case['expected'] === null || $blended->status === 'none') {
                    continue;
                }
                $right = $blended->results[0]->id === $case['expected'];
                $score += (int) ($right === ($blended->status === 'ok'));
            }
            if ($score > $bestScore) {
                $best = $margin;
                $bestScore = $score;
            }
        }

        return $best;
    }

    /**
     * The highest threshold that keeps the expected article on the search page as often as the lexical
     * setting does, so the blend's extra matches do not fill the page with noise. Capped at min_score,
     * because a question the chatbot can answer must also be findable.
     */
    private function bestSearchThreshold(RankingService $ranking, float $minScore): float
    {
        $limit = (int) config('search.search_limit');
        $listed = fn (array $results, float $min, ?int $expected): bool => in_array(
            $expected,
            array_slice(array_map(fn (RankedArticle $r): int => $r->id, array_values(array_filter($results, fn (RankedArticle $r): bool => $r->score > $min))), 0, $limit),
            true,
        );

        $found = array_values(array_filter($this->tune, fn (array $case): bool => $case['expected'] !== null));
        $target = 0;
        foreach ($found as $case) {
            $target += (int) $listed($case['lexical']->results, (float) config('search.search_min_score'), $case['expected']);
        }

        $rows = array_map(fn (array $case): array => [$this->blendCase($ranking, $case)->results, $case['expected']], $found);
        $best = self::THRESHOLD_STEP;
        for ($threshold = self::THRESHOLD_STEP; $threshold <= $minScore + 1e-9; $threshold += self::THRESHOLD_STEP) {
            $kept = 0;
            foreach ($rows as [$results, $expected]) {
                $kept += (int) $listed($results, $threshold, $expected);
            }
            if ($kept >= $target) {
                $best = round($threshold, 2);
            }
        }

        return $best;
    }

    /** @return array{int, int} The chosen settings' tune results from the real Evaluator, as counts. */
    private function verify(RankingService $ranking): array
    {
        $blended = [];
        foreach ($this->tune as $case) {
            $blended[$case['lexical']->query] = $this->blendCase($ranking, $case);
        }

        $report = (new Evaluator(['blend' => fn (string $question): RankResult => $blended[$question]], $this->tuneCases()))->run('blend', 'tune');

        return [(int) round($report['top1'] * $report['total']), (int) round($report['top3'] * $report['total'])];
    }

    /** @param  array{lexical: RankResult, cosines: array<int, float>}  $case */
    private function blendCase(RankingService $ranking, array $case): RankResult
    {
        return $ranking->blend($case['lexical'], $case['cosines']);
    }
}
