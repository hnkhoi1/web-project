<?php

namespace Tests\Feature\Search;

use App\Search\ArticleRepository;
use App\Search\LexicalScorer;
use Database\Seeders\LawSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Normalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LexicalParityTest extends TestCase
{
    use RefreshDatabase;
    use UsesFrozenDictionary;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LawSeeder::class);
        $this->useFrozenDictionary();
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function goldenProvider(): array
    {
        $golden = json_decode(
            (string) file_get_contents(__DIR__.'/../../fixtures/scorer-golden.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $cases = [];
        foreach ($golden as $entry) {
            $cases[$entry['question']] = [$entry];
        }

        return $cases;
    }

    /** @param array<string, mixed> $entry */
    #[DataProvider('goldenProvider')]
    public function test_rank_matches_legacy_js(array $entry): void
    {
        $expected = $entry['rank'];
        $r = app(LexicalScorer::class)->rank($entry['question'], app(ArticleRepository::class)->all());

        $this->assertSame($expected['mode'], $r->mode);
        $this->assertSame($expected['status'], $r->status);
        $this->assertSame($expected['concepts'], $r->concepts);
        $this->assertSame(
            $expected['missingTarget'] === null ? null : (int) $expected['missingTarget'],
            $r->missingTarget,
        );
        $this->assertSame('lexical', $r->engine);

        $top5 = array_slice($r->results, 0, 5);
        $this->assertSame(
            array_column($expected['top5'], 'key'),
            array_map(fn ($x): string => (string) $x->id, $top5),
        );
        foreach ($expected['top5'] as $i => $want) {
            $this->assertEqualsWithDelta($want['score'], $top5[$i]->score, 1e-9, "score #{$i}");
            foreach (['concept', 'lexical', 'fuzzy'] as $part) {
                $this->assertEqualsWithDelta($want['parts'][$part], $top5[$i]->parts[$part], 1e-9, "{$part} #{$i}");
            }
        }
    }

    /** @return array<string, array{string, string}> */
    public static function roleProvider(): array
    {
        return [
            'victim hit' => ['tôi bị đánh', 'nan_nhan'],
            'victim scammed' => ['bị lừa mất tiền', 'nan_nhan'],
            'offender arrested' => ['tôi đánh bạc bị công an bắt', 'nguoi_vi_pham'],
            'offender slip' => ['tôi lỡ đánh người', 'nguoi_vi_pham'],
            'unclear' => ['thời tiết hôm nay', 'chua_ro'],
            'unaccented unclear' => ['toi bi danh', 'chua_ro'],
        ];
    }

    #[DataProvider('roleProvider')]
    public function test_role(string $query, string $role): void
    {
        $this->assertSame($role, LexicalScorer::detectRole($query));
        $this->assertSame($role, app(LexicalScorer::class)->rank($query, app(ArticleRepository::class)->all())->role);
    }

    public function test_nfd_query_ranks_like_nfc(): void
    {
        $articles = app(ArticleRepository::class)->all();
        $scorer = app(LexicalScorer::class);
        $nfc = $scorer->rank('tôi bị đánh', $articles);
        $nfd = $scorer->rank(Normalizer::normalize('tôi bị đánh', Normalizer::FORM_D), $articles);

        $this->assertSame('co_dau', $nfd->mode);
        $this->assertSame($nfc->status, $nfd->status);
        $this->assertSame($nfc->concepts, $nfd->concepts);
        $this->assertSame(
            array_map(fn ($x): int => $x->id, $nfc->results),
            array_map(fn ($x): int => $x->id, $nfd->results),
        );
        $this->assertSame($nfc->results[0]->score, $nfd->results[0]->score);
    }

    public function test_invalid_utf8_query_has_no_results(): void
    {
        $r = app(LexicalScorer::class)->rank("\xFF", app(ArticleRepository::class)->all());

        $this->assertSame('none', $r->status);
    }
}
