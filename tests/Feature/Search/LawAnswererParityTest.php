<?php

namespace Tests\Feature\Search;

use App\Search\LawAnswerer;
use Database\Seeders\LawSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LawAnswererParityTest extends TestCase
{
    use RefreshDatabase;

    private const KEYS = ['found', 'mode', 'score', 'alt', 'article', 'khoan', 'khoan_all', 'ask', 'quyen_loi',
        'can_cu', 'ghi_chu_quyen_loi', 'note_title', 'note_text'];

    private const FOLLOW_UPS = [
        [128, 'vô ý làm chết người', 'bao nhiêu người tử vong'], [133, 'đe dọa giết người', 'nội dung đe dọa cụ thể'],
        [135, 'bị kích động mạnh gây thương tích', 'bao nhiêu phần trăm'], [138, 'vô ý gây thương tích', 'bao nhiêu phần trăm'],
        [155, 'bị chửi trên facebook', 'nội dung xúc phạm'], [156, 'bị vu khống trên mạng', 'thông tin nào'],
        [171, 'bị cướp giật điện thoại', 'bao nhiêu triệu đồng'], [173, 'bị trộm xe máy', 'bao nhiêu triệu đồng'],
        [175, 'vay tiền không trả', 'bao nhiêu triệu đồng'], [322, 'tổ chức đánh bạc', 'bao nhiêu người'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LawSeeder::class);
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
    public function test_answer_matches_legacy_js(array $entry): void
    {
        $expected = $entry['answer'];
        $actual = app(LawAnswerer::class)->answer($entry['question']);

        $this->assertSame($expected['found'], $actual['found']);
        foreach (self::KEYS as $key) {
            $this->assertSame(array_key_exists($key, $expected), array_key_exists($key, $actual), "{$entry['question']}: key {$key}");
            if (! array_key_exists($key, $expected)) {
                continue;
            }
            if ($key === 'score') {
                $this->assertEqualsWithDelta($expected[$key], $actual[$key], 1e-9);
            } else {
                $this->assertSame($expected[$key], $actual[$key], "{$entry['question']}: {$key}");
            }
        }
    }

    public function test_followups_ask_for_missing_figures(): void
    {
        foreach (self::FOLLOW_UPS as [$id, $question, $phrase]) {
            $a = app(LawAnswerer::class)->answer($question);
            $this->assertTrue($a['found'], $question);
            $this->assertSame('overview', $a['mode'], $question);
            $this->assertSame($id, $a['article']['id'], $question);
            $this->assertStringContainsString($phrase, mb_strtolower($a['ask']), $question);
        }
    }

    public function test_conversations_reach_specific_clause(): void
    {
        $answerer = app(LawAnswerer::class);

        $first = $answerer->answer('vay tiền bị quịt thì phải làm sao');
        $this->assertSame('overview', $first['mode']);
        $second = $answerer->answer('vay tiền bị quịt thì phải làm sao 80 triệu đồng');
        $this->assertSame(175, $second['article']['id']);
        $this->assertSame('specific', $second['mode']);

        $third = $answerer->answer('tôi bị đánh gây thương tích, giờ xử lý thế nào 20%');
        $this->assertSame(134, $third['article']['id']);
        $this->assertSame('specific', $third['mode']);
    }
}
