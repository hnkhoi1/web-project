<?php

namespace App\Console\Commands;

use App\Search\Evaluator;
use App\Search\RankingService;
use App\Search\RankResult;
use Illuminate\Console\Command;
use InvalidArgumentException;

class SearchEval extends Command
{
    protected $signature = 'search:eval
        {--engine=lexical : lexical, semantic or blend}
        {--split=all : tune, holdout or all}
        {--json : Print the report as JSON}';

    protected $description = 'Score a search engine against the evaluation set';

    public function handle(RankingService $ranking): int
    {
        $evaluator = new Evaluator([
            'lexical' => fn (string $question): RankResult => $ranking->rank($question),
        ]);

        $engine = (string) $this->option('engine');
        $split = (string) $this->option('split');

        try {
            $report = $evaluator->run($engine, $split);
        } catch (InvalidArgumentException $e) {
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
}
