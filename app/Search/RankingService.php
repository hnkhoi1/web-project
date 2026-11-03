<?php

namespace App\Search;

/** The only entry point for ranking articles against a question (lexical now; blended with semantic ranking later). */
class RankingService
{
    public function __construct(
        private readonly LexicalScorer $scorer,
        private readonly ArticleRepository $articles,
    ) {}

    /**
     * @param  list<array<string, mixed>>|null  $articles  Defaults to every article.
     */
    public function rank(string $query, ?array $articles = null): RankResult
    {
        return $this->scorer->rank($query, $articles ?? $this->articles->all());
    }
}
