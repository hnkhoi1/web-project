<?php

namespace Tests\Feature\Search;

use App\Search\ChatConversation;
use Database\Seeders\LawSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatConversationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LawSeeder::class);
    }

    private function chat(): ChatConversation
    {
        return app(ChatConversation::class);
    }

    public function test_without_pending_the_message_is_the_question(): void
    {
        $this->assertSame('hello', $this->chat()->resolve('hello', null));
    }

    public function test_bare_number_takes_pending_unit(): void
    {
        $p = ['question' => 'vay tiền', 'unit' => 'triệu', 'article_id' => 175];
        $this->assertSame('vay tiền 80 triệu đồng', $this->chat()->resolve('80', $p));

        $pct = ['question' => 'q', 'unit' => '%', 'article_id' => 134];
        $this->assertSame('q 20%', $this->chat()->resolve('20', $pct));
        $this->assertSame('q 2 tỷ đồng', $this->chat()->resolve('2 tỷ', $pct));
    }

    public function test_new_topic_drops_context(): void
    {
        $p = ['question' => 'vay tiền', 'unit' => 'triệu', 'article_id' => 175];
        $this->assertSame('bị trộm xe máy', $this->chat()->resolve('bị trộm xe máy', $p));
    }

    public function test_unrelated_text_is_appended(): void
    {
        $p = ['question' => 'vay tiền', 'unit' => 'triệu', 'article_id' => 175];
        $this->assertSame('vay tiền. Thông tin bổ sung: anh ấy không trả', $this->chat()->resolve('anh ấy không trả', $p));
    }

    public function test_result_is_capped(): void
    {
        $p = ['question' => str_repeat('a', 1990), 'unit' => null, 'article_id' => 175];
        $this->assertSame(2000, mb_strlen($this->chat()->resolve('anh ấy không trả', $p)));
        $this->assertSame(2000, mb_strlen($this->chat()->resolve(str_repeat('b', 3000), null)));
    }

    public function test_next_pending(): void
    {
        $art = ['id' => 175];
        $c = $this->chat();
        $this->assertSame(['question' => 'q', 'unit' => '%', 'article_id' => 175],
            $c->nextPending('q', ['found' => true, 'mode' => 'overview', 'ask' => 'Cho biết tỉ lệ', 'article' => $art]));
        $this->assertSame('triệu',
            $c->nextPending('q', ['found' => true, 'mode' => 'overview', 'ask' => 'bao nhiêu triệu đồng', 'article' => $art])['unit']);
        $this->assertNull(
            $c->nextPending('q', ['found' => true, 'mode' => 'overview', 'ask' => 'nội dung cụ thể', 'article' => $art])['unit']);
        $this->assertNull($c->nextPending('q', ['found' => true, 'mode' => 'specific', 'article' => $art]));
        $this->assertNull($c->nextPending('q', ['found' => false]));
    }
}
