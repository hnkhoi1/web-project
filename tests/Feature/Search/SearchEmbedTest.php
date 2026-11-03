<?php

namespace Tests\Feature\Search;

use App\Models\Article;
use App\Models\Clause;
use App\Models\LawEmbedding;
use App\Search\ArticleRepository;
use App\Search\EmbeddingStore;
use Database\Seeders\LawSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SearchEmbedTest extends TestCase
{
    use RefreshDatabase;

    private const MODEL = 'intfloat/multilingual-e5-small';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'search.embedder_url' => 'http://embedder:8000',
            'search.embedder_model' => self::MODEL,
            'search.embedder_dimension' => 3,
        ]);
        $this->seed(LawSeeder::class);
    }

    /** @var list<float> */
    private array $vector = [0.0, 0.6, 0.8];

    private function fakeEmbedder(): void
    {
        Http::fake([
            'embedder:8000/health' => Http::response(['status' => 'ok']),
            'embedder:8000/embed' => fn (Request $r) => Http::response([
                'model' => self::MODEL,
                'dimension' => 3,
                'vectors' => array_map(fn () => $this->vector, $r['texts']),
            ]),
        ]);
    }

    private function embedCalls(): int
    {
        return Http::recorded(fn (Request $r) => str_ends_with($r->url(), '/embed'))->count();
    }

    public function test_creates_a_row_per_article_and_clause(): void
    {
        $this->fakeEmbedder();

        $this->artisan('search:embed', ['--wait' => 0])->assertExitCode(0);

        $this->assertSame(Article::count(), LawEmbedding::where('so_khoan', 0)->count());
        $this->assertSame(Clause::count(), LawEmbedding::where('so_khoan', '>', 0)->count());

        $article = Article::query()->orderBy('id')->firstOrFail();
        $row = LawEmbedding::where('dieu_luat_id', $article->id)->where('so_khoan', 0)->firstOrFail();
        $this->assertSame(self::MODEL, $row->model);
        $this->assertEquals([0.0, 0.6, 0.8], $row->vector);
        $this->assertSame(hash('sha256', trim("{$article->tieu_de}\n{$article->tom_tat}\n{$article->tu_khoa}")), $row->content_hash);

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/embed')
            && in_array(trim("{$article->tieu_de}\n{$article->tom_tat}\n{$article->tu_khoa}"), $r['texts'], true));
    }

    public function test_second_run_writes_nothing_and_changed_keywords_reembed_one_row(): void
    {
        $this->fakeEmbedder();
        $this->artisan('search:embed', ['--wait' => 0])->assertExitCode(0);

        $before = $this->embedCalls();
        $this->artisan('search:embed', ['--wait' => 0])->assertExitCode(0);
        $this->assertSame($before, $this->embedCalls());
        $this->assertGreaterThan(0, $before);

        $clause = Clause::query()->firstOrFail();
        $clause->update(['tu_khoa' => 'từ khóa mới hoàn toàn']);
        app()->forgetInstance(ArticleRepository::class);
        $old = LawEmbedding::where('dieu_luat_id', $clause->dieu_luat_id)->where('so_khoan', $clause->so_khoan)->firstOrFail();

        $this->vector = [1.0, 0.0, 0.0];
        $this->artisan('search:embed', ['--wait' => 0])->assertExitCode(0);

        $this->assertSame($before + 1, $this->embedCalls());
        $this->assertEquals([1.0, 0.0, 0.0], $old->fresh()->vector);
        $this->assertNotSame($old->content_hash, $old->fresh()->content_hash);
    }

    public function test_embedder_down_exits_zero_and_keeps_existing_rows(): void
    {
        $this->fakeEmbedder();
        $this->artisan('search:embed', ['--wait' => 0])->assertExitCode(0);
        $count = LawEmbedding::count();

        Http::fake(fn () => throw new ConnectionException('refused'));
        Article::query()->orderBy('id')->firstOrFail()->update(['tu_khoa' => 'đổi']);
        app()->forgetInstance(ArticleRepository::class);

        $this->artisan('search:embed', ['--wait' => 0])
            ->expectsOutputToContain('không phản hồi')
            ->assertExitCode(0);

        $this->assertSame($count, LawEmbedding::count());
    }

    public function test_a_duplicate_article_level_row_is_rejected(): void
    {
        $this->fakeEmbedder();
        $this->artisan('search:embed', ['--wait' => 0])->assertExitCode(0);
        $row = LawEmbedding::where('so_khoan', 0)->firstOrFail();

        $this->expectException(UniqueConstraintViolationException::class);

        LawEmbedding::create([
            'dieu_luat_id' => $row->dieu_luat_id,
            'so_khoan' => 0,
            'model' => $row->model,
            'content_hash' => str_repeat('a', 64),
            'vector' => [0.0, 0.6, 0.8],
        ]);
    }

    public function test_store_groups_vectors_by_article(): void
    {
        $this->fakeEmbedder();
        $this->artisan('search:embed', ['--wait' => 0])->assertExitCode(0);

        $vectors = app(EmbeddingStore::class)->vectorsFor(self::MODEL);

        $article = Article::with('clauses')->orderBy('id')->firstOrFail();
        $this->assertCount(1 + $article->clauses->count(), $vectors[$article->id]);
        $this->assertSame([], app(EmbeddingStore::class)->vectorsFor('other-model'));
    }
}
