<?php

namespace Tests\Feature\Search;

use App\Search\ArticleRepository;
use App\Search\ChatConversation;
use App\Search\LawSearch;
use App\Search\LexicalScorer;
use App\Search\RankingService;
use Database\Seeders\LawSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** The model is optional and off by default: pure lexical search even when vectors are stored. */
class BlendDisabledTest extends TestCase
{
    use RefreshDatabase;
    use UsesCachedVectors;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LawSeeder::class);
        $this->useCachedVectors();
    }

    public function test_the_blend_is_off_by_default(): void
    {
        $this->assertFalse(config('search.blend.enabled'));
    }

    public function test_disabled_is_identical_to_the_lexical_scorer_without_a_call(): void
    {
        foreach (['vay tiền không trả', 'hàng xóm gây chuyện trên đường', 'thời tiết hôm nay thế nào'] as $query) {
            $result = app(RankingService::class)->rank($query);

            $this->assertSame('lexical', $result->engine);
            $this->assertEquals(app(LexicalScorer::class)->rank($query, app(ArticleRepository::class)->all()), $result);
        }

        Http::assertNothingSent();
    }

    public function test_disabled_does_not_look_up_vectors(): void
    {
        DB::enableQueryLog();

        app(RankingService::class)->rank('vay tiền không trả');

        $this->assertSame([], array_values(array_filter(
            array_column(DB::getQueryLog(), 'query'),
            fn (string $sql): bool => str_contains($sql, 'law_embeddings'),
        )));
    }

    public function test_disabled_search_and_chat_use_the_original_thresholds(): void
    {
        // Both blend thresholds are unreachable, so any use of them would change these results.
        config(['search.blend.search_min_score' => 2.0, 'search.blend.new_topic_min_score' => 2.0]);

        $this->assertNotEmpty(app(LawSearch::class)->search('vay tiền không trả'));
        $this->assertSame('bị trộm xe máy', app(ChatConversation::class)->resolve(
            'bị trộm xe máy',
            ['question' => 'vay tiền', 'unit' => 'triệu', 'article_id' => 175],
        ));

        Http::assertNothingSent();
    }
}
