<?php

namespace App\Search;

/**
 * The only entry point for ranking articles against a question: plain lexical scores unless
 * search.blend.enabled turns on the optional blend with semantic scores.
 */
class RankingService
{
    public function __construct(
        private readonly LexicalScorer $scorer,
        private readonly ArticleRepository $articles,
        private readonly EmbeddingClient $client,
        private readonly EmbeddingStore $store,
        private readonly SemanticScorer $semantic,
    ) {}

    /**
     * @param  list<array<string, mixed>>|null  $articles  Defaults to every article.
     */
    public function rank(string $query, ?array $articles = null): RankResult
    {
        return config('search.blend.enabled')
            ? $this->rankBlended($query, $articles)
            : $this->rankLexical($query, $articles);
    }

    /**
     * Lexical blended with semantic scores whatever the setting, falling back to the plain lexical
     * result whenever the vectors or the embedder are not usable.
     *
     * @param  list<array<string, mixed>>|null  $articles  Defaults to every article.
     */
    public function rankBlended(string $query, ?array $articles = null): RankResult
    {
        $lexical = $this->rankLexical($query, $articles);
        $cosines = $this->cosines($query, array_map(fn (RankedArticle $r): int => $r->id, $lexical->results));

        return $cosines === null ? $lexical : $this->blend($lexical, $cosines);
    }

    /**
     * @param  list<array<string, mixed>>|null  $articles  Defaults to every article.
     */
    public function rankLexical(string $query, ?array $articles = null): RankResult
    {
        return $this->scorer->rank($query, $articles ?? $this->articles->all());
    }

    /**
     * Semantic scores alone, for measuring what they contribute: the blend with no lexical part, judged
     * against the same min_score. Not used by the site.
     *
     * @throws EngineUnavailable When the vectors or the embedder are not usable.
     */
    public function rankSemantic(string $query): RankResult
    {
        $lexical = $this->rankLexical($query);
        $cosines = $this->cosines($query, array_map(fn (RankedArticle $r): int => $r->id, $lexical->results))
            ?? throw new EngineUnavailable('Engine "semantic" is unavailable: it needs stored vectors and a working embedder.');

        return $this->blend($lexical, $cosines, 0.0, 1.0, 1.0, 'semantic');
    }

    /**
     * Combines a lexical result with each article's best cosine.
     *
     * @param  array<int, float>  $cosines
     */
    public function blend(
        RankResult $lexical,
        array $cosines,
        ?float $lexicalWeight = null,
        ?float $semanticWeight = null,
        ?float $semanticWeightWithConcept = null,
        string $engine = 'blend',
    ): RankResult {
        $lexicalWeight ??= (float) config('search.blend.lexical_weight');
        $weight = $lexical->concepts !== []
            ? ($semanticWeightWithConcept ?? (float) config('search.blend.semantic_weight_with_concept'))
            : ($semanticWeight ?? (float) config('search.blend.semantic_weight'));
        $scores = $this->semantic->scale($cosines);

        $results = array_map(fn (RankedArticle $r): RankedArticle => new RankedArticle(
            id: $r->id,
            article: $r->article,
            score: min(1.0, $lexicalWeight * $r->score + $weight * $scores[$r->id]),
            parts: $r->parts + ['semantic' => $scores[$r->id]],
            why: $r->why,
        ), $lexical->results);
        usort($results, fn (RankedArticle $a, RankedArticle $b): int => $b->score <=> $a->score);

        return new RankResult(
            query: $lexical->query,
            mode: $lexical->mode,
            role: $lexical->role,
            status: $this->status($results, $lexical->missingTarget),
            results: $results,
            concepts: $lexical->concepts,
            missingTarget: $lexical->missingTarget,
            engine: $engine,
        );
    }

    /**
     * The same logic as the lexical scorer, on the blend's thresholds.
     *
     * @param  list<RankedArticle>  $results
     */
    private function status(array $results, ?int $missingTarget): string
    {
        $min = (float) config('search.blend.min_score');
        $top = $results[0] ?? null;
        $second = $results[1] ?? null;

        if ($missingTarget !== null || $top === null || $top->score < $min) {
            return 'none';
        }

        return $second !== null && $second->score >= $min && $top->score - $second->score < (float) config('search.blend.margin')
            ? 'ambiguous'
            : 'ok';
    }

    /**
     * Vectors are checked before the embedder is called, so a site without them never touches HTTP.
     *
     * @param  list<int>  $ids  The articles being ranked.
     * @return array<int, float>|null Best cosine per article, or null when any of it is unusable.
     */
    private function cosines(string $query, array $ids): ?array
    {
        $stored = $this->store->vectorsFor((string) config('search.embedder_model'));
        $vectors = array_intersect_key($stored, array_flip($ids));
        if ($ids === [] || count($vectors) !== count($ids) || ! $this->dimensionsMatch($vectors)) {
            return null;
        }

        $queryVector = $this->client->embedQuery($query);

        return $queryVector === null ? null : $this->semantic->cosines($queryVector, $vectors);
    }

    /** @param  array<int, list<list<float>>>  $vectors */
    private function dimensionsMatch(array $vectors): bool
    {
        $dimension = (int) config('search.embedder_dimension');
        foreach ($vectors as $articleVectors) {
            foreach ($articleVectors as $vector) {
                if (count($vector) !== $dimension) {
                    return false;
                }
            }
        }

        return true;
    }
}
