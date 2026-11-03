<?php

namespace Tests\Feature\Search;

use App\Search\ArticleRepository;
use App\Search\ChatConversation;
use App\Search\LawAnswerer;
use App\Search\RankingService;
use App\Search\RegressionSet;
use Database\Seeders\LawSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** The original question list (tests/scorer-test.js) must keep passing on the blend, on recorded vectors. */
class SearchRegressionTest extends TestCase
{
    use RefreshDatabase;
    use UsesCachedVectors;

    /** @var array{questions: list<array{string, int}>, none: list<string>, follow_ups: list<array{int, string, string}>, conversations: list<array{string, string, int}>, chat_context: list<array{int, string, string, string}>} */
    private array $regression;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LawSeeder::class);
        $this->useCachedVectors();
        $this->regression = json_decode((string) file_get_contents(base_path('tests/fixtures/search-regression.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_every_original_question_finds_its_article_on_the_blend(): void
    {
        $this->assertCount(47, $this->regression['questions']);

        $failures = [];
        foreach ($this->regression['questions'] as [$question, $expected]) {
            $result = app(RankingService::class)->rank($question);
            $this->assertSame('blend', $result->engine);
            if ($result->results[0]->id !== $expected || $result->status === 'none') {
                $failures[] = "{$question}: expected {$expected}, got {$result->results[0]->id} ({$result->status})";
            }
        }

        $this->assertSame([], $failures);
    }

    public function test_out_of_scope_questions_find_nothing_on_the_blend(): void
    {
        foreach ($this->regression['none'] as $question) {
            $result = app(RankingService::class)->rank($question);
            $this->assertSame('blend', $result->engine);
            $this->assertSame('none', $result->status, $question);
        }
    }

    public function test_follow_up_questions_are_asked_through_the_answerer(): void
    {
        $this->assertCount(10, $this->regression['follow_ups']);
        $articles = collect(app(ArticleRepository::class)->all())->keyBy('id');

        foreach ($this->regression['follow_ups'] as [$id, $question, $phrase]) {
            $clauses = $articles[$id]['khoan'];
            $this->assertGreaterThanOrEqual(2, count($clauses));
            foreach ($clauses as $clause) {
                $this->assertNotEmpty($clause['cau_hoi_lam_ro']);
            }

            $answer = app(LawAnswerer::class)->answer($question);
            $this->assertTrue($answer['found'], $question);
            $this->assertSame($id, $answer['article']['id'], $question);
            $this->assertSame('overview', $answer['mode'], $question);
            $this->assertStringContainsString($phrase, mb_strtolower($answer['ask']), $question);
        }
    }

    public function test_conversations_reach_a_specific_clause_through_the_answerer(): void
    {
        $this->assertCount(2, $this->regression['conversations']);

        foreach ($this->regression['conversations'] as [$initial, $continued, $id]) {
            $first = app(LawAnswerer::class)->answer($initial);
            $this->assertTrue($first['found'], $initial);
            $this->assertSame('overview', $first['mode'], $initial);

            $second = app(LawAnswerer::class)->answer($continued);
            $this->assertTrue($second['found'], $continued);
            $this->assertSame($id, $second['article']['id'], $continued);
            $this->assertSame('specific', $second['mode'], $continued);
            $this->assertGreaterThan(0, $second['khoan']['so_khoan'], $continued);
        }
    }

    public function test_new_topic_detection_works_on_the_blend(): void
    {
        foreach ($this->regression['chat_context'] as [$articleId, $question, $message, $expected]) {
            $pending = ['question' => $question, 'unit' => null, 'article_id' => $articleId];
            $this->assertSame(
                $expected === 'new_topic' ? $message : "{$question}. Thông tin bổ sung: {$message}",
                app(ChatConversation::class)->resolve($message, $pending),
            );
        }
    }

    public function test_tuning_never_embeds_a_holdout_question(): void
    {
        $storage = sys_get_temp_dir().'/search-tune-test';
        File::ensureDirectoryExists("{$storage}/app");
        $this->app->useStoragePath($storage);

        $this->artisan('search:tune')->assertExitCode(0);
        $this->assertFileExists("{$storage}/app/search-tune.json");
        File::deleteDirectory($storage);

        $cases = json_decode((string) file_get_contents(base_path('tests/fixtures/search-eval.json')), true, flags: JSON_THROW_ON_ERROR);
        $tune = array_column(array_filter($cases, fn (array $c): bool => $c['split'] === 'tune'), 'question');
        $holdoutOnly = array_diff(array_column(array_filter($cases, fn (array $c): bool => $c['split'] === 'holdout'), 'question'), $tune, RegressionSet::load()->questions());
        $this->assertNotEmpty($holdoutOnly);

        $embedded = Http::recorded()->flatMap(fn (array $pair) => $pair[0]['texts'])->all();
        $this->assertSame([], array_values(array_intersect($holdoutOnly, $embedded)));
    }
}
