<?php

namespace App\Search;

final readonly class RankedArticle
{
    /**
     * @param  array<string, mixed>  $article
     * @param  array{concept: float, lexical: float, fuzzy: float}  $parts
     * @param  list<string>  $why
     */
    public function __construct(
        public int $id,
        public array $article,
        public float $score,
        public array $parts,
        public array $why,
    ) {}
}
