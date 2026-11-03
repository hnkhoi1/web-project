<?php

namespace Tests\Feature\Search;

use App\Models\LawEmbedding;
use App\Models\User;
use App\Search\ArticleRepository;
use App\Search\LexicalScorer;
use App\Search\RankingService;
use Closure;
use Database\Seeders\LawSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Any trouble with the embedder or its vectors must leave the original lexical search intact. */
class EmbedderFallbackTest extends TestCase
{
    use RefreshDatabase;

    private const QUERY = 'vay tiền không trả';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LawSeeder::class);
    }

    /** @param  list<int>|null  $only  Article ids to store vectors for (default: every article). */
    private function storeVectors(?string $model = null, ?array $only = null): void
    {
        foreach (app(ArticleRepository::class)->all() as $article) {
            if ($only !== null && ! in_array($article['id'], $only, true)) {
                continue;
            }
            LawEmbedding::create([
                'dieu_luat_id' => $article['id'],
                'so_khoan' => LawEmbedding::ARTICLE,
                'model' => $model ?? config('search.embedder_model'),
                'content_hash' => str_repeat('a', 64),
                'vector' => $this->vector(),
            ]);
        }
    }

    /** @return list<float> */
    private function vector(int $dimension = 384): array
    {
        return array_fill(0, $dimension, 1.0 / sqrt($dimension));
    }

    private function assertLexical(): void
    {
        $result = app(RankingService::class)->rank(self::QUERY);
        $lexical = app(LexicalScorer::class)->rank(self::QUERY, app(ArticleRepository::class)->all());

        $this->assertSame('lexical', $result->engine);
        $this->assertSame($lexical->status, $result->status);
        $this->assertEquals($lexical->results, $result->results);
        $this->assertEquals($lexical, $result);
    }

    /** @return array<string, array{Closure(self): mixed}> */
    public static function failures(): array
    {
        return [
            'timeout' => [fn () => throw new ConnectionException('cURL error 28: timed out')],
            '500' => [fn () => Http::response('boom', 500)],
            'wrong dimension' => [fn (self $t) => Http::response([
                'model' => config('search.embedder_model'),
                'dimension' => 3,
                'vectors' => [[0.1, 0.2, 0.3]],
            ])],
            'other model' => [fn (self $t) => Http::response([
                'model' => 'someone/else',
                'dimension' => 384,
                'vectors' => [$t->vector()],
            ])],
        ];
    }

    #[DataProvider('failures')]
    public function test_embedder_failure_falls_back_to_lexical(Closure $response): void
    {
        $this->storeVectors();
        $calls = 0;
        Http::fake(['*' => function () use ($response, &$calls) {
            $calls++;

            return $response($this);
        }]);

        $this->assertLexical();
        $this->assertSame(1, $calls);
    }

    public function test_no_vectors_stored_is_lexical_without_a_call(): void
    {
        Http::fake();

        $this->assertLexical();
        Http::assertNothingSent();
    }

    public function test_vectors_only_for_another_model_are_lexical_without_a_call(): void
    {
        $this->storeVectors('someone/else');
        Http::fake();

        $this->assertLexical();
        Http::assertNothingSent();
    }

    public function test_vectors_covering_only_some_articles_are_lexical_without_a_call(): void
    {
        $this->storeVectors(only: [134, 175]);
        Http::fake();

        $this->assertLexical();
        Http::assertNothingSent();
    }

    public function test_a_stored_vector_of_the_wrong_dimension_is_lexical(): void
    {
        $this->storeVectors();
        LawEmbedding::query()->where('dieu_luat_id', 134)->update(['vector' => json_encode([0.1, 0.2])]);
        Http::fake();

        $this->assertLexical();
        Http::assertNothingSent();
    }

    public function test_the_search_page_works_with_the_embedder_down(): void
    {
        $this->storeVectors();
        Http::fake(['*' => fn () => throw new ConnectionException('down')]);

        $this->actingAs(User::factory()->create())
            ->get('/tra-cuu?q='.urlencode(self::QUERY))
            ->assertOk();
    }

    public function test_the_chatbot_works_with_the_embedder_down(): void
    {
        $this->storeVectors();
        Http::fake(['*' => fn () => throw new ConnectionException('down')]);

        $this->actingAs(User::factory()->create())
            ->postJson('/tu-van/hoi', ['message' => self::QUERY])
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('article.id', 175);
    }
}
