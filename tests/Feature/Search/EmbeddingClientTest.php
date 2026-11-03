<?php

namespace Tests\Feature\Search;

use App\Search\EmbeddingClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EmbeddingClientTest extends TestCase
{
    private const MODEL = 'intfloat/multilingual-e5-small';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'search.embedder_url' => 'http://embedder:8000',
            'search.embedder_model' => self::MODEL,
            'search.embedder_dimension' => 3,
        ]);
    }

    /** @param  list<list<float>>  $vectors */
    private function ok(array $vectors, string $model = self::MODEL, int $dimension = 3): array
    {
        return ['model' => $model, 'dimension' => $dimension, 'vectors' => $vectors];
    }

    public function test_embed_query_returns_the_vector(): void
    {
        Http::fake(['embedder:8000/embed' => Http::response($this->ok([[1.0, 0.0, 0.0]]))]);

        $this->assertSame([1.0, 0.0, 0.0], app(EmbeddingClient::class)->embedQuery('xin chào'));

        Http::assertSent(fn ($r) => $r['kind'] === 'query' && $r['texts'] === ['xin chào']);
    }

    public function test_embed_passages_returns_all_vectors(): void
    {
        Http::fake(['embedder:8000/embed' => Http::response($this->ok([[1.0, 0.0, 0.0], [0.0, 1.0, 0.0]]))]);

        $out = app(EmbeddingClient::class)->embedPassages(['a', 'b']);

        $this->assertSame([[1.0, 0.0, 0.0], [0.0, 1.0, 0.0]], $out);
        Http::assertSent(fn ($r) => $r['kind'] === 'passage');
    }

    /** @return array<string, array{0: callable}> */
    public static function failures(): array
    {
        return [
            'timeout' => [fn () => throw new ConnectionException('timed out')],
            'server error' => [fn () => Http::response('boom', 500)],
            'malformed json' => [fn () => Http::response('not json', 200)],
            'missing vectors' => [fn () => Http::response(['model' => self::MODEL])],
            'wrong dimension' => [fn () => Http::response(['model' => self::MODEL, 'dimension' => 3, 'vectors' => [[1.0, 0.0]]])],
            'model mismatch' => [fn () => Http::response(['model' => 'other', 'dimension' => 3, 'vectors' => [[1.0, 0.0, 0.0]]])],
            'count mismatch' => [fn () => Http::response(['model' => self::MODEL, 'dimension' => 3, 'vectors' => []])],
        ];
    }

    #[DataProvider('failures')]
    public function test_failures_return_null_and_log_a_warning(callable $response): void
    {
        Http::fake(['embedder:8000/embed' => $response]);
        Log::spy();

        $this->assertNull(app(EmbeddingClient::class)->embedQuery('xin chào'));
        $this->assertNull(app(EmbeddingClient::class)->embedPassages(['xin chào']));

        Log::shouldHaveReceived('warning')->atLeast()->twice();
    }
}
