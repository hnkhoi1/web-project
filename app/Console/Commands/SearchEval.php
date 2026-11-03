<?php

namespace App\Console\Commands;

use App\Models\LawEmbedding;
use App\Search\EmbeddingClient;
use App\Search\EngineUnavailable;
use App\Search\Evaluator;
use App\Search\RankingService;
use App\Search\RankResult;
use App\Search\RegressionSet;
use Illuminate\Console\Command;
use InvalidArgumentException;

class SearchEval extends Command
{
    protected $signature = 'search:eval
        {--engine=lexical : lexical, semantic or blend}
        {--split=all : tune, holdout or all}
        {--json : Print the report as JSON}
        {--dump : Record query and passage vectors for the tests (no scoring)}';

    protected $description = 'Score a search engine against the evaluation set';

    public function handle(RankingService $ranking, EmbeddingClient $client): int
    {
        if ($this->option('dump')) {
            return $this->dump($client);
        }

        $evaluator = new Evaluator([
            'lexical' => fn (string $question): RankResult => $ranking->rankLexical($question),
            'semantic' => fn (string $question): RankResult => $ranking->rankSemantic($question),
            'blend' => fn (string $question): RankResult => $ranking->rankBlended($question),
        ]);

        $engine = (string) $this->option('engine');
        $split = (string) $this->option('split');

        try {
            $report = $evaluator->run($engine, $split);
        } catch (InvalidArgumentException|EngineUnavailable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        $this->line("engine={$engine} split={$split} total={$report['total']}");
        $this->line(sprintf('top-1: %.1f%%', $report['top1'] * 100));
        $this->line(sprintf('top-3: %.1f%%', $report['top3'] * 100));
        $this->newLine();
        $this->line('Failures: '.count($report['failures']));

        foreach ($report['failures'] as $failure) {
            $this->line(sprintf(
                '%s expected=%s alternates=[%s] status=%s top3=[%s] top-1:%s top-3:%s | %s',
                $failure['id'],
                $failure['expected'] ?? 'none',
                implode(',', $failure['alternates']),
                $failure['status'],
                implode(',', $failure['top3']),
                $failure['top1_correct'] ? 'ok' : 'miss',
                $failure['top3_correct'] ? 'ok' : 'miss',
                $failure['question'],
            ));
        }

        return self::SUCCESS;
    }

    /** Writes the recorded vectors the offline tests replay. Sees only the questions and never scores them. */
    private function dump(EmbeddingClient $client): int
    {
        $eval = json_decode((string) file_get_contents(base_path('tests/fixtures/search-eval.json')), true, flags: JSON_THROW_ON_ERROR);
        $questions = [...array_column($eval, 'question'), ...RegressionSet::load()->questions()];

        $vectors = [];
        foreach (array_unique($questions) as $question) {
            $vector = $client->embedQuery($question);
            if ($vector === null) {
                $this->error("Embedder failed on: {$question}");

                return self::FAILURE;
            }
            $vectors[$question] = $this->rounded($vector);
        }
        $this->write('query-vectors.json', $vectors);

        $passages = LawEmbedding::query()
            ->where('model', config('search.embedder_model'))
            ->orderBy('dieu_luat_id')
            ->orderBy('so_khoan')
            ->get()
            ->map(fn (LawEmbedding $row): array => [
                'dieu_luat_id' => (int) $row->dieu_luat_id,
                'so_khoan' => (int) $row->so_khoan,
                'vector' => $this->rounded((array) $row->vector),
            ])
            ->all();
        $this->write('passage-vectors.json', $passages);

        $this->line(count($vectors).' query vectors, '.count($passages).' passage vectors written.');

        return self::SUCCESS;
    }

    /**
     * @param  array<array-key, mixed>  $vector
     * @return list<float>
     */
    private function rounded(array $vector): array
    {
        return array_map(fn (mixed $v): float => round((float) $v, 6), array_values($vector));
    }

    private function write(string $file, mixed $data): void
    {
        file_put_contents(base_path("tests/fixtures/{$file}"), json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
    }
}
