<?php

namespace Tests\Feature\Search;

use App\Search\ArticleRepository;
use Database\Seeders\LawSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SearchStatusTest extends TestCase
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
        app()->forgetInstance(ArticleRepository::class);
    }

    private function fakeEmbedder(): void
    {
        Http::fake([
            'embedder:8000/health' => Http::response(['status' => 'ok']),
            'embedder:8000/embed' => fn (Request $r) => Http::response([
                'model' => self::MODEL,
                'dimension' => 3,
                'vectors' => array_map(fn () => [0.0, 0.6, 0.8], $r['texts']),
            ]),
        ]);
    }

    public function test_fails_and_says_how_to_fix_it_when_there_is_no_law_data(): void
    {
        Http::fake(['*' => Http::response([], 500)]);

        $this->artisan('search:status')
            ->expectsOutputToContain('migrate --seed')
            ->assertFailed();
    }

    public function test_the_keyword_mode_needs_nothing_else(): void
    {
        $this->seed(LawSeeder::class);
        Http::fake(['*' => Http::response([], 500)]);
        config(['search.blend.enabled' => false]);

        $this->artisan('search:status')
            ->expectsOutputToContain('tắt (chỉ dùng từ khóa)')
            ->expectsOutputToContain('Chế độ từ khóa luôn sẵn sàng')
            ->assertSuccessful();
    }

    public function test_warns_when_blend_is_on_but_the_embedder_is_down(): void
    {
        $this->seed(LawSeeder::class);
        Http::fake(fn () => throw new ConnectionException('down'));
        config(['search.blend.enabled' => true]);

        $this->artisan('search:status')
            ->expectsOutputToContain('không phản hồi')
            ->expectsOutputToContain('embedder không chạy')
            ->assertSuccessful();
    }

    public function test_warns_about_missing_vectors_and_clears_once_embedded(): void
    {
        $this->seed(LawSeeder::class);
        $this->fakeEmbedder();
        config(['search.blend.enabled' => true]);

        $this->artisan('search:status')
            ->expectsOutputToContain('Thiếu vector')
            ->assertSuccessful();

        $this->artisan('search:embed', ['--wait' => 0])->assertSuccessful();

        $this->artisan('search:status')
            ->expectsOutputToContain('Chế độ trộn sẵn sàng')
            ->assertSuccessful();
    }
}
