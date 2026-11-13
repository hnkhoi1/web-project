<?php

namespace Tests\Feature;

use App\Models\ChatHistory;
use App\Models\User;
use Database\Seeders\LawSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ChatHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LawSeeder::class);
    }

    private function ask(User $user, string $question, ?int $article = 173): ChatHistory
    {
        return ChatHistory::create([
            'user_id' => $user->id,
            'cau_hoi' => $question,
            'dieu_luat_id' => $article,
            'khoan_so' => $article ? 1 : null,
            'diem' => $article ? 0.8 : null,
        ]);
    }

    public function test_guests_are_sent_to_login(): void
    {
        $row = $this->ask(User::factory()->create(), 'trộm xe');

        $this->get(route('history.index'))->assertRedirect('/login');
        $this->get(route('history.show', $row->id))->assertRedirect('/login');
        $this->patch(route('history.rate', $row->id), ['danh_gia' => 'dung'])->assertRedirect('/login');
        $this->delete(route('history.destroy', $row->id))->assertRedirect('/login');
        $this->delete(route('history.clear'))->assertRedirect('/login');
    }

    public function test_ask_returns_the_history_id_of_the_saved_question(): void
    {
        $user = User::factory()->create();

        $id = $this->actingAs($user)
            ->postJson(route('chat.ask'), ['message' => 'Tôi bị trộm mất xe máy trị giá 30 triệu'])
            ->assertOk()
            ->assertJsonStructure(['found', 'history_id'])
            ->json('history_id');

        $row = ChatHistory::findOrFail($id);
        $this->assertSame($user->id, $row->user_id);
        $this->assertNull($row->danh_gia);
    }

    public function test_the_list_shows_what_the_user_typed_not_the_merged_context(): void
    {
        $me = User::factory()->create();
        $row = $this->ask($me, 'bị trộm xe. Thông tin bổ sung: 30 triệu');
        $row->forceFill(['tin_nhan' => '30 triệu'])->save();
        $this->ask($me, 'câu cũ không có tin nhắn riêng');

        $this->actingAs($me)->get(route('history.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('history.data.0.cau_hoi', 'câu cũ không có tin nhắn riêng')
                ->where('history.data.1.cau_hoi', '30 triệu'));

        $this->actingAs($me)->get(route('history.show', $row->id))
            ->assertInertia(fn (Assert $page) => $page->where('item.cau_hoi', '30 triệu')->where('answer.found', true));
    }

    public function test_a_follow_up_stores_both_the_typed_text_and_the_merged_question(): void
    {
        $user = User::factory()->create();

        $first = $this->actingAs($user)->postJson(route('chat.ask'), ['message' => 'Tôi bị trộm mất xe máy'])->json('history_id');
        $this->assertSame('Tôi bị trộm mất xe máy', ChatHistory::find($first)->tin_nhan);
    }

    public function test_an_unmatched_question_also_gets_an_id_so_it_can_be_rated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('chat.ask'), ['message' => 'xyz abc qwe'])
            ->assertOk()
            ->assertJson(['found' => false])
            ->assertJsonStructure(['history_id']);
    }

    public function test_the_list_shows_only_my_questions_newest_first(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $this->ask($me, 'câu cũ')->forceFill(['created_at' => now()->subDay()])->save();
        $this->ask($me, 'câu mới');
        $this->ask($other, 'câu của người khác');

        $this->actingAs($me)->get(route('history.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('chat/history')
                ->has('history.data', 2)
                ->where('history.data.0.cau_hoi', 'câu mới')
                ->where('history.data.1.cau_hoi', 'câu cũ'));
    }

    public function test_the_list_can_be_searched_literally(): void
    {
        $me = User::factory()->create();
        $this->ask($me, 'giảm 50% mức phạt');
        $this->ask($me, 'trộm điện thoại');

        $this->actingAs($me)->get(route('history.index', ['q' => '%']))
            ->assertInertia(fn (Assert $page) => $page->has('history.data', 1));

        $this->actingAs($me)->get(route('history.index', ['q' => 'trộm']))
            ->assertInertia(fn (Assert $page) => $page->has('history.data', 1)->where('filters.q', 'trộm'));
    }

    public function test_an_empty_history_renders(): void
    {
        $this->actingAs(User::factory()->create())->get(route('history.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('history.data', 0));
    }

    public function test_reopening_a_question_recomputes_the_answer(): void
    {
        $me = User::factory()->create();
        $row = $this->ask($me, 'Tôi bị trộm mất xe máy trị giá 30 triệu');

        $this->actingAs($me)->get(route('history.show', $row->id))
            ->assertInertia(fn (Assert $page) => $page
                ->component('chat/history-show')
                ->where('item.id', $row->id)
                ->where('answer.found', true)
                ->where('answer.article.id', 173));
    }

    public function test_another_users_question_is_a_404(): void
    {
        $row = $this->ask(User::factory()->create(), 'bí mật');
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('history.show', $row->id))->assertNotFound();
        $this->actingAs($intruder)->patch(route('history.rate', $row->id), ['danh_gia' => 'sai'])->assertNotFound();
        $this->actingAs($intruder)->delete(route('history.destroy', $row->id))->assertNotFound();

        $row->refresh();
        $this->assertNull($row->danh_gia);
        $this->assertDatabaseHas('lich_su_hoi_dap', ['id' => $row->id]);
    }

    public function test_a_rating_can_be_set_changed_and_cleared(): void
    {
        $me = User::factory()->create();
        $row = $this->ask($me, 'trộm xe');

        $this->actingAs($me)->patchJson(route('history.rate', $row->id), ['danh_gia' => 'dung'])
            ->assertOk()->assertJson(['danh_gia' => 'dung']);
        $this->assertSame('dung', $row->fresh()->danh_gia);

        $this->actingAs($me)->patchJson(route('history.rate', $row->id), ['danh_gia' => 'sai'])->assertOk();
        $this->assertSame('sai', $row->fresh()->danh_gia);

        $this->actingAs($me)->patchJson(route('history.rate', $row->id), ['danh_gia' => null])
            ->assertOk()->assertJson(['danh_gia' => null]);
        $this->assertNull($row->fresh()->danh_gia);
    }

    public function test_a_rating_from_a_plain_form_redirects_back(): void
    {
        $me = User::factory()->create();
        $row = $this->ask($me, 'trộm xe');

        $this->actingAs($me)->from(route('history.index'))
            ->patch(route('history.rate', $row->id), ['danh_gia' => 'dung'])
            ->assertRedirect(route('history.index'));

        $this->assertSame('dung', $row->fresh()->danh_gia);
    }

    public function test_an_invalid_rating_is_rejected(): void
    {
        $me = User::factory()->create();
        $row = $this->ask($me, 'trộm xe');

        $this->actingAs($me)->patchJson(route('history.rate', $row->id), ['danh_gia' => 'tuyet-voi'])
            ->assertUnprocessable()->assertJsonValidationErrors('danh_gia');

        $this->assertNull($row->fresh()->danh_gia);
    }

    public function test_a_question_can_be_deleted(): void
    {
        $me = User::factory()->create();
        $row = $this->ask($me, 'xóa tôi');
        $keep = $this->ask($me, 'giữ tôi');

        $this->actingAs($me)->delete(route('history.destroy', $row->id))->assertRedirect(route('history.index'));

        $this->assertDatabaseMissing('lich_su_hoi_dap', ['id' => $row->id]);
        $this->assertDatabaseHas('lich_su_hoi_dap', ['id' => $keep->id]);
    }

    public function test_clearing_removes_only_my_questions(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $this->ask($me, 'một');
        $this->ask($me, 'hai');
        $theirs = $this->ask($other, 'của người khác');

        $this->actingAs($me)->delete(route('history.clear'))->assertRedirect(route('history.index'));

        $this->assertSame(0, ChatHistory::where('user_id', $me->id)->count());
        $this->assertDatabaseHas('lich_su_hoi_dap', ['id' => $theirs->id]);
    }

    public function test_deleting_a_user_keeps_their_anonymous_history_for_the_admin(): void
    {
        $me = User::factory()->create();
        $row = $this->ask($me, 'câu hỏi còn lại');

        $me->delete();

        $this->assertNull($row->fresh()->user_id);
    }

    public function test_the_admin_sees_ratings_and_can_filter_wrong_answers(): void
    {
        $user = User::factory()->create();
        $this->ask($user, 'đúng rồi')->forceFill(['danh_gia' => 'dung'])->save();
        $this->ask($user, 'sai rồi')->forceFill(['danh_gia' => 'sai'])->save();
        $this->ask($user, 'chưa đánh giá');
        $admin = User::factory()->create();
        $admin->role = User::ROLE_ADMIN;
        $admin->save();

        $this->actingAs($admin)->get(route('admin.index'))
            ->assertInertia(fn (Assert $page) => $page->where('stats.rated', 2)->where('stats.ratedWrong', 1));

        $this->actingAs($admin)->get(route('admin.questions.index', ['loai' => 'sai']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('questions.data', 1)
                ->where('questions.data.0.cau_hoi', 'sai rồi')
                ->where('questions.data.0.danh_gia', 'sai')
                ->where('filters.loai', 'sai'));
    }
}
