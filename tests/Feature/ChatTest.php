<?php

namespace Tests\Feature;

use App\Models\ChatHistory;
use App\Models\User;
use Database\Seeders\LawSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private const OVERVIEW_Q = 'vay tiền bị quịt thì phải làm sao';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LawSeeder::class);
        $this->user = User::factory()->create();
    }

    private function ask(string $message, array $extra = [])
    {
        return $this->actingAs($this->user)->postJson('/tu-van/hoi', ['message' => $message] + $extra);
    }

    public function test_guest_cannot_use_chat(): void
    {
        $this->postJson('/tu-van/hoi', ['message' => 'abc'])->assertUnauthorized();
        $this->get('/tu-van')->assertRedirect('/login');
    }

    public function test_overview_answer_is_logged_and_sets_pending(): void
    {
        $this->ask(self::OVERVIEW_Q)
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('mode', 'overview')
            ->assertJsonPath('article.id', 175)
            ->assertSessionHas('chat.pending');

        $row = ChatHistory::query()->sole();
        $this->assertSame($this->user->id, $row->user_id);
        $this->assertSame(self::OVERVIEW_Q, $row->cau_hoi);
        $this->assertSame(175, $row->dieu_luat_id);
        $this->assertGreaterThan(0, $row->diem);
    }

    public function test_follow_up_number_gives_specific_answer_and_clears_pending(): void
    {
        $this->ask(self::OVERVIEW_Q);
        $this->ask('80')
            ->assertOk()
            ->assertJsonPath('mode', 'specific')
            ->assertJsonPath('article.id', 175)
            ->assertSessionMissing('chat.pending');

        $row = ChatHistory::query()->latest('id')->first();
        $this->assertStringContainsString('80 triệu đồng', $row->cau_hoi);
        $this->assertNotNull($row->khoan_so);
    }

    public function test_new_topic_while_pending_drops_context(): void
    {
        $this->ask(self::OVERVIEW_Q);
        $this->ask('tàng trữ ma túy trong người bị xử lý ra sao')
            ->assertJsonPath('found', true)
            ->assertJsonPath('article.id', fn ($id) => $id !== 175);
    }

    public function test_not_found_is_logged_without_article(): void
    {
        $this->ask('thời tiết hôm nay thế nào')
            ->assertOk()
            ->assertJsonPath('found', false)
            ->assertSessionMissing('chat.pending');

        $row = ChatHistory::query()->sole();
        $this->assertNull($row->dieu_luat_id);
        $this->assertNull($row->khoan_so);
        $this->assertNull($row->diem);
    }

    public function test_number_without_pending_is_not_found(): void
    {
        $this->ask('20')->assertOk()->assertJsonPath('found', false);
    }

    public function test_validation(): void
    {
        $this->ask('')->assertUnprocessable();
        $this->ask('   ')->assertUnprocessable();
        $this->ask(str_repeat('a', 501))->assertUnprocessable();
        $this->assertSame(0, ChatHistory::count());
    }

    public function test_visiting_page_clears_pending(): void
    {
        $this->ask(self::OVERVIEW_Q)->assertSessionHas('chat.pending');
        $this->get('/tu-van')->assertOk()->assertSessionMissing('chat.pending');
    }

    public function test_role_is_victim_for_injury_question(): void
    {
        $this->ask('tôi bị đánh gây thương tích 20%')->assertJsonPath('role', 'nan_nhan');
    }

    public function test_client_cannot_influence_stored_article_or_score(): void
    {
        $this->ask('thời tiết hôm nay thế nào', ['dieu_luat_id' => 1, 'diem' => 0.99, 'khoan_so' => 3, 'user_id' => 999]);

        $row = ChatHistory::query()->sole();
        $this->assertSame($this->user->id, $row->user_id);
        $this->assertNull($row->dieu_luat_id);
        $this->assertNull($row->diem);
        $this->assertNull($row->khoan_so);
    }
}
