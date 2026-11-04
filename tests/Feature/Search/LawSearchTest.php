<?php

namespace Tests\Feature\Search;

use App\Search\ArticleRepository;
use App\Search\LawSearch;
use Database\Seeders\LawSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LawSearchTest extends TestCase
{
    use RefreshDatabase;
    use UsesFrozenDictionary;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LawSeeder::class);
        $this->useFrozenDictionary();
    }

    private function search(string $q, string $chuong = ''): array
    {
        return app(LawSearch::class)->search($q, $chuong);
    }

    public function test_empty_query_lists_everything_without_score(): void
    {
        $all = $this->search('');
        $this->assertCount(18, $all);
        $this->assertArrayNotHasKey('score', $all[0]);
    }

    public function test_empty_query_filters_by_chapter(): void
    {
        $chapter = app(ArticleRepository::class)->chapters()[0];
        $rows = $this->search('', $chapter);
        $this->assertNotEmpty($rows);
        foreach ($rows as $row) {
            $this->assertSame($chapter, $row['chuong']);
        }
    }

    #[DataProvider('numberQueries')]
    public function test_article_number_lookup(string $q): void
    {
        $rows = $this->search($q);
        $this->assertSame([134], array_column($rows, 'id'));
        $this->assertEquals(1, $rows[0]['score']);
    }

    /** @return array<string, array{string}> */
    public static function numberQueries(): array
    {
        return ['điều 134' => ['điều 134'], '134' => ['134'], 'Dieu 134' => ['Dieu 134'], 'Điều 134' => ['Điều 134']];
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function goldenProvider(): array
    {
        $golden = json_decode((string) file_get_contents(__DIR__.'/../../fixtures/scorer-golden.json'), true, 512, JSON_THROW_ON_ERROR);
        $cases = [];
        foreach ($golden as $entry) {
            $cases[$entry['question']] = [$entry];
        }

        return $cases;
    }

    /** @param array<string, mixed> $entry */
    #[DataProvider('goldenProvider')]
    public function test_search_matches_legacy_js(array $entry): void
    {
        $rows = $this->search($entry['question']);
        $this->assertSame(array_column($entry['search'], 'id'), array_column($rows, 'id'), $entry['question']);
        foreach ($entry['search'] as $i => $want) {
            if ($want['score'] === null) {
                $this->assertArrayNotHasKey('score', $rows[$i]);
            } else {
                $this->assertEqualsWithDelta($want['score'], $rows[$i]['score'], 1e-9);
            }
        }
    }

    public function test_gambling_search(): void
    {
        $rows = $this->search('đánh bạc');
        $this->assertSame(321, $rows[0]['id']);
        $this->assertLessThanOrEqual(15, count($rows));
        foreach ($rows as $row) {
            $this->assertGreaterThan(0.08, $row['score']);
        }
    }
}
