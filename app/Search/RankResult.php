<?php

namespace App\Search;

final readonly class RankResult
{
    /**
     * @param  string  $mode  co_dau|khong_dau
     * @param  string  $status  ok|ambiguous|none
     * @param  list<RankedArticle>  $results  Sorted by score, descending.
     * @param  list<string>  $concepts
     * @param  string  $engine  lexical|blend
     */
    public function __construct(
        public string $query,
        public string $mode,
        public string $role,
        public string $status,
        public array $results,
        public array $concepts,
        public ?int $missingTarget,
        public string $engine = 'lexical',
    ) {}
}
