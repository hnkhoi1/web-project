<?php

namespace Tests\Feature;

use App\Models\User;
use App\Search\ArticleRepository;
use Database\Seeders\LawSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LawPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LawSeeder::class);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/tra-cuu')->assertRedirect('/login');
        $this->get('/dieu-luat/134')->assertRedirect('/login');
    }

    public function test_search_ranks_results_with_score_and_lists_chapters(): void
    {
        $chapters = app(ArticleRepository::class)->chapters();

        $this->actingAs(User::factory()->create())
            ->get('/tra-cuu?q='.rawurlencode('đánh bạc'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('law/search')
                ->where('q', 'đánh bạc')
                ->where('results.0.id', 321)
                ->has('results.0.score')
                ->has('chapters', count($chapters)));
    }

    public function test_unknown_chapter_is_treated_as_all(): void
    {
        $total = count(app(ArticleRepository::class)->all());

        $this->actingAs(User::factory()->create())
            ->get('/tra-cuu?chuong=unknown')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('chuong', '')
                ->has('results', $total));
    }

    public function test_known_chapter_filters(): void
    {
        $repo = app(ArticleRepository::class);
        $name = $repo->chapters()[0];
        $expected = count(array_filter($repo->all(), fn ($a) => $a['chuong'] === $name));

        $this->actingAs(User::factory()->create())
            ->get('/tra-cuu?chuong='.rawurlencode($name))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('chuong', $name)
                ->has('results', $expected));
    }

    public function test_long_query_is_truncated_to_200_characters(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/tra-cuu?q='.rawurlencode(str_repeat('đ', 300)))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('q', str_repeat('đ', 200)));
    }

    public function test_invalid_utf8_and_array_params_behave_as_empty(): void
    {
        $user = User::factory()->create();
        $total = count(app(ArticleRepository::class)->all());

        foreach (['/tra-cuu?q=%FF', '/tra-cuu?q[]=x', '/tra-cuu?chuong[]=x', '/tra-cuu?chuong=%FF'] as $url) {
            $this->actingAs($user)->get($url)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('q', '')
                    ->where('chuong', '')
                    ->has('results', $total));
        }
    }

    public function test_show_returns_article_with_clauses_and_related(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dieu-luat/134')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('law/show')
                ->where('article.id', 134)
                ->has('article.khoan')
                ->has('article.can_cu_boi_thuong')
                ->has('related')
                ->where('related', fn ($related) => count($related) <= 4 && count($related) > 0));
    }

    public function test_show_404s_for_unknown_or_non_numeric_ids(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dieu-luat/999')->assertNotFound();
        $this->actingAs($user)->get('/dieu-luat/abc')->assertNotFound();
    }
}
