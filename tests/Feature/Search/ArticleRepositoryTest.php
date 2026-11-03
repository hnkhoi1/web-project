<?php

namespace Tests\Feature\Search;

use App\Search\ArticleRepository;
use Database\Seeders\LawSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ArticleRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private const ARTICLE_KEYS = [
        'id', 'chuong', 'tieu_de', 'tom_tat', 'tu_khoa', 'khung_hinh_phat',
        'can_cu_boi_thuong', 'ghi_chu_quyen_loi', 'khoan',
    ];

    private const CLAUSE_KEYS = [
        'so_khoan', 'tu_khoa', 'dieu_kien', 'ti_le_ton_thuong_min', 'ti_le_ton_thuong_max',
        'gia_tri_tai_san_min', 'gia_tri_tai_san_max', 'so_nguoi_chet_min', 'so_nguoi_chet_max',
        'noi_dung', 'quyen_loi', 'cau_hoi_lam_ro',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LawSeeder::class);
    }

    /** @return list<array<string, mixed>> */
    private function source(): array
    {
        return require database_path('data/dieu_luat_data.php');
    }

    private function repo(): ArticleRepository
    {
        return app(ArticleRepository::class);
    }

    public function test_round_trip(): void
    {
        $actual = $this->repo()->all();
        $source = $this->source();
        usort($source, fn ($a, $b) => $a['id'] <=> $b['id']);

        $this->assertCount(count($source), $actual);

        foreach ($source as $i => $expected) {
            $got = $actual[$i];
            $this->assertEqualsCanonicalizing(self::ARTICLE_KEYS, array_keys($got));
            foreach (self::ARTICLE_KEYS as $key) {
                if ($key === 'khoan') {
                    continue;
                }
                $this->assertEquals($expected[$key] ?? null, $got[$key], "article {$expected['id']} {$key}");
            }

            $this->assertCount(count($expected['khoan']), $got['khoan']);
            foreach ($expected['khoan'] as $j => $clause) {
                $gotClause = $got['khoan'][$j];
                $this->assertEqualsCanonicalizing(self::CLAUSE_KEYS, array_keys($gotClause));
                foreach (self::CLAUSE_KEYS as $key) {
                    $this->assertEquals(
                        $clause[$key] ?? null,
                        $gotClause[$key],
                        "article {$expected['id']} clause {$clause['so_khoan']} {$key}",
                    );
                }
            }
        }
    }

    public function test_numeric_types(): void
    {
        $seenFloat = $seenInt = false;
        foreach ($this->repo()->all() as $article) {
            foreach ($article['khoan'] as $c) {
                foreach (['ti_le_ton_thuong_min', 'ti_le_ton_thuong_max', 'gia_tri_tai_san_min', 'gia_tri_tai_san_max'] as $k) {
                    $this->assertTrue($c[$k] === null || is_float($c[$k]), $k);
                    $seenFloat = $seenFloat || is_float($c[$k]);
                }
                foreach (['so_nguoi_chet_min', 'so_nguoi_chet_max'] as $k) {
                    $this->assertTrue($c[$k] === null || is_int($c[$k]), $k);
                    $seenInt = $seenInt || is_int($c[$k]);
                }
                $this->assertIsInt($c['so_khoan']);
            }
        }
        $this->assertTrue($seenFloat && $seenInt);
    }

    public function test_count_and_find(): void
    {
        $this->assertCount(18, $this->repo()->all());
        $source = collect($this->source())->firstWhere('id', 134);
        $this->assertSame($source['tieu_de'], $this->repo()->find(134)['tieu_de']);
        $this->assertNull($this->repo()->find(999));
    }

    public function test_vietnamese_roundtrip(): void
    {
        $source = collect($this->source())->firstWhere('id', 134);
        $stored = DB::table('dieu_luat')->where('id', 134)->value('tieu_de');
        $this->assertSame(bin2hex($source['tieu_de']), bin2hex($stored));

        $collation = DB::selectOne(
            'SELECT COLLATION_NAME AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['dieu_luat', 'tieu_de'],
        )->c;
        $this->assertSame('utf8mb4_unicode_ci', $collation);
    }

    public function test_related(): void
    {
        $article = $this->repo()->find(134);
        $related = $this->repo()->related($article);

        $this->assertNotEmpty($related);
        $this->assertLessThanOrEqual(4, count($related));
        foreach ($related as $r) {
            $this->assertSame($article['chuong'], $r['chuong']);
            $this->assertNotSame(134, $r['id']);
        }
    }

    public function test_chapters_in_book_order(): void
    {
        $expected = array_values(array_unique(array_column($this->source(), 'chuong')));
        $this->assertSame($expected, $this->repo()->chapters());
    }
}
