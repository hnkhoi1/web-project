<?php

namespace Tests\Feature\Search;

use App\Models\LawEmbedding;
use App\Search\ArticleRepository;
use App\Search\ChatConversation;
use App\Search\LawSearch;
use App\Search\LexicalScorer;
use App\Search\RankedArticle;
use App\Search\RankingService;
use App\Search\SemanticScorer;
use Database\Seeders\LawSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** The blend, on fixed vectors: each article points along its own axis, the query points where the test says. */
class RankingBlendTest extends TestCase
{
    use RefreshDatabase;

    private const NO_CONCEPT_QUERY = 'hàng xóm gây chuyện trên đường';

    /** @var list<float> */
    private array $queryVector = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LawSeeder::class);

        config([
            'search.blend.lexical_weight' => 0.6,
            'search.blend.semantic_weight' => 0.4,
            'search.blend.semantic_weight_with_concept' => 0.1,
            'search.blend.cos_floor' => 0.0,
            'search.blend.cos_ceil' => 1.0,
            'search.blend.min_score' => 0.2,
            'search.blend.margin' => 0.05,
            'search.blend.search_min_score' => 0.3,
            'search.blend.new_topic_min_score' => 0.4,
        ]);

        $model = config('search.embedder_model');
        foreach ($this->articles() as $index => $article) {
            LawEmbedding::create([
                'dieu_luat_id' => $article['id'],
                'so_khoan' => LawEmbedding::ARTICLE,
                'model' => $model,
                'content_hash' => str_repeat('a', 64),
                'vector' => $this->axis($index),
            ]);
        }

        Http::fake(['*' => fn () => Http::response([
            'model' => $model,
            'dimension' => 384,
            'vectors' => [$this->queryVector],
        ])]);
    }

    /** @return list<array<string, mixed>> */
    private function articles(): array
    {
        return app(ArticleRepository::class)->all();
    }

    private function articleAxis(int $id): int
    {
        return (int) array_search($id, array_column($this->articles(), 'id'), true);
    }

    /** @return list<float> */
    private function axis(int $index): array
    {
        $vector = array_fill(0, 384, 0.0);
        $vector[$index] = 1.0;

        return $vector;
    }

    public function test_semantic_lifts_the_right_article_when_no_concept_hit(): void
    {
        $lexical = app(LexicalScorer::class)->rank(self::NO_CONCEPT_QUERY, $this->articles());
        $this->assertSame([], $lexical->concepts);
        $this->assertSame(260, $lexical->results[0]->id);

        $this->queryVector = $this->axis($this->articleAxis(134));
        $result = app(RankingService::class)->rank(self::NO_CONCEPT_QUERY);

        $this->assertSame('blend', $result->engine);
        $this->assertSame(134, $result->results[0]->id);
        $this->assertEqualsWithDelta(1.0, $result->results[0]->parts['semantic'], 1e-9);
    }

    public function test_a_strong_concept_keeps_its_target_first_against_a_conflicting_semantic_score(): void
    {
        $this->queryVector = $this->axis($this->articleAxis(134));
        $result = app(RankingService::class)->rank('đánh bạc');

        $this->assertSame('blend', $result->engine);
        $this->assertSame(['danh_bac'], $result->concepts);
        $this->assertSame(321, $result->results[0]->id);
        $this->assertEqualsWithDelta(1.0, $this->resultFor($result->results, 134)->parts['semantic'], 1e-9);
    }

    public function test_final_score_follows_the_formula_for_each_weight(): void
    {
        $this->queryVector = $this->axis($this->articleAxis(134));
        $lexical = app(LexicalScorer::class)->rank(self::NO_CONCEPT_QUERY, $this->articles());
        $result = app(RankingService::class)->rank(self::NO_CONCEPT_QUERY);

        $expected = 0.6 * $this->resultFor($lexical->results, 134)->score + 0.4 * 1.0;
        $this->assertEqualsWithDelta($expected, $this->resultFor($result->results, 134)->score, 1e-9);

        $lexical = app(LexicalScorer::class)->rank('đánh bạc', $this->articles());
        $result = app(RankingService::class)->rank('đánh bạc');
        $expected = 0.6 * $this->resultFor($lexical->results, 134)->score + 0.1 * 1.0;
        $this->assertEqualsWithDelta($expected, $this->resultFor($result->results, 134)->score, 1e-9);
    }

    public function test_results_are_sorted_descending_with_scores_in_range(): void
    {
        $this->queryVector = $this->axis($this->articleAxis(134));
        $results = app(RankingService::class)->rank('đánh bạc')->results;

        $this->assertCount(18, $results);
        $previous = 1.0;
        foreach ($results as $result) {
            $this->assertLessThanOrEqual($previous, $result->score);
            $this->assertGreaterThanOrEqual(0.0, $result->score);
            $previous = $result->score;
        }
    }

    public function test_a_chapter_filtered_pool_only_returns_that_chapter(): void
    {
        $chapter = app(ArticleRepository::class)->chapters()[0];
        $pool = array_values(array_filter($this->articles(), fn (array $a) => $a['chuong'] === $chapter));
        $outsider = array_values(array_filter($this->articles(), fn (array $a) => $a['chuong'] !== $chapter))[0];
        $this->queryVector = $this->axis($this->articleAxis($outsider['id']));

        $result = app(RankingService::class)->rank(self::NO_CONCEPT_QUERY, $pool);

        $this->assertSame('blend', $result->engine);
        $this->assertCount(count($pool), $result->results);
        foreach ($result->results as $ranked) {
            $this->assertSame($chapter, $ranked->article['chuong']);
        }
    }

    public function test_status_uses_the_blend_thresholds(): void
    {
        $this->queryVector = $this->axis($this->articleAxis(134));

        config(['search.blend.min_score' => 0.99]);
        $this->assertSame('none', app(RankingService::class)->rank(self::NO_CONCEPT_QUERY)->status);

        config(['search.blend.min_score' => 0.1, 'search.blend.margin' => 0.9]);
        $this->assertSame('ambiguous', app(RankingService::class)->rank(self::NO_CONCEPT_QUERY)->status);

        config(['search.blend.margin' => 0.001]);
        $this->assertSame('ok', app(RankingService::class)->rank(self::NO_CONCEPT_QUERY)->status);
    }

    public function test_a_missing_target_still_forces_none(): void
    {
        $pool = array_values(array_filter($this->articles(), fn (array $a) => $a['id'] !== 173));
        $this->queryVector = $this->axis($this->articleAxis(134));

        $result = app(RankingService::class)->rank('bị trộm xe máy', $pool);

        $this->assertSame('blend', $result->engine);
        $this->assertSame(173, $result->missingTarget);
        $this->assertSame('none', $result->status);
    }

    public function test_law_search_uses_the_blend_search_threshold(): void
    {
        // Only the semantic target clears 0.3: lexical alone is at most 0.6 * 0.224 here.
        $this->queryVector = $this->axis($this->articleAxis(134));

        $rows = app(LawSearch::class)->search(self::NO_CONCEPT_QUERY);

        $this->assertSame([134], array_column($rows, 'id'));
    }

    public function test_chat_uses_the_blend_new_topic_threshold(): void
    {
        $pending = ['question' => 'vay tiền', 'unit' => 'triệu', 'article_id' => 175];
        $this->queryVector = $this->axis($this->articleAxis(134));

        // Blended top is 134 at about 0.4 + a little lexical, which clears a 0.4 threshold but not 0.9.
        $this->assertSame(self::NO_CONCEPT_QUERY, app(ChatConversation::class)->resolve(self::NO_CONCEPT_QUERY, $pending));

        config(['search.blend.new_topic_min_score' => 0.9]);
        $this->assertSame(
            'vay tiền. Thông tin bổ sung: '.self::NO_CONCEPT_QUERY,
            app(ChatConversation::class)->resolve(self::NO_CONCEPT_QUERY, $pending),
        );
    }

    public function test_semantic_scorer_takes_the_best_vector_of_each_article_and_clamps(): void
    {
        $scores = app(SemanticScorer::class)->scores([1.0, 0.0], [
            1 => [[0.0, 1.0], [1.0, 0.0]],
            2 => [[0.6, 0.8]],
            3 => [[-1.0, 0.0]],
        ]);

        $this->assertEqualsWithDelta(1.0, $scores[1], 1e-9);
        $this->assertEqualsWithDelta(0.6, $scores[2], 1e-9);
        $this->assertEqualsWithDelta(0.0, $scores[3], 1e-9);
    }

    /** @param  list<RankedArticle>  $results */
    private function resultFor(array $results, int $id): RankedArticle
    {
        foreach ($results as $result) {
            if ($result->id === $id) {
                return $result;
            }
        }
        $this->fail("Article {$id} not in results");
    }
}
