<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Chapter;
use App\Models\ChatHistory;
use App\Models\Clause;
use App\Models\LawEmbedding;
use App\Models\User;
use App\Search\ArticleRepository;
use App\Search\LawSearch;
use Database\Seeders\LawSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminLawTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LawSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->role = User::ROLE_ADMIN;
        $user->save();

        return $user;
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_replace([
            'id' => 500,
            'chuong' => 'Các tội xâm phạm tính mạng, sức khỏe',
            'tieu_de' => 'Tội thử nghiệm kiểm định',
            'tom_tat' => 'Điều luật dùng cho kiểm thử.',
            'tu_khoa' => 'kiemdinhthunghiem',
            'khung_hinh_phat' => 'Phạt tù 1–5 năm',
            'can_cu_boi_thuong' => '',
            'ghi_chu_quyen_loi' => '',
            'khoan' => [
                ['so_khoan' => 1, 'noi_dung' => 'Mức nhẹ.', 'ti_le_ton_thuong_min' => 11, 'ti_le_ton_thuong_max' => 30],
                ['so_khoan' => 2, 'noi_dung' => 'Mức nặng.', 'ti_le_ton_thuong_min' => '31', 'ti_le_ton_thuong_max' => ''],
            ],
        ], $overrides);
    }

    public function test_only_admins_can_reach_the_law_pages(): void
    {
        $this->get(route('admin.laws.index'))->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get(route('admin.laws.index'))->assertNotFound();
        $this->actingAs(User::factory()->create())->post(route('admin.laws.store'), $this->payload())->assertNotFound();
        $this->actingAs(User::factory()->create())->delete(route('admin.laws.destroy', 134))->assertNotFound();
        $this->actingAs(User::factory()->demo()->create())->get(route('admin.laws.create'))->assertNotFound();

        $this->assertDatabaseMissing('dieu_luat', ['id' => 500]);
        $this->assertDatabaseHas('dieu_luat', ['id' => 134]);
    }

    public function test_the_list_shows_every_article_with_counts_and_can_be_searched(): void
    {
        ChatHistory::create(['cau_hoi' => 'bị đánh', 'dieu_luat_id' => 134]);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.laws.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/laws/index')
                ->has('articles', 18)
                ->where('articles', fn ($rows) => collect($rows)->firstWhere('id', 134)['answers_count'] === 1
                    && collect($rows)->firstWhere('id', 134)['clauses_count'] === Clause::where('dieu_luat_id', 134)->count()));

        $this->actingAs($admin)->get(route('admin.laws.index', ['q' => '134']))
            ->assertInertia(fn (Assert $page) => $page->has('articles', 1)->where('articles.0.id', 134));

        $this->actingAs($admin)->get(route('admin.laws.index', ['q' => 'thương tích']))
            ->assertInertia(fn (Assert $page) => $page->where('articles.0.id', 134));
    }

    public function test_the_edit_form_is_prefilled(): void
    {
        $this->actingAs($this->admin())->get(route('admin.laws.edit', 134))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/laws/form')
                ->where('article.id', 134)
                ->where('article.chuong', 'Các tội xâm phạm tính mạng, sức khỏe')
                ->where('article.khoan.0.so_khoan', 1)
                ->has('chapters'));

        $this->actingAs($this->admin())->get(route('admin.laws.edit', 999))->assertNotFound();
    }

    public function test_an_admin_can_add_an_article_that_search_finds_right_away(): void
    {
        $this->actingAs($this->admin())->post(route('admin.laws.store'), $this->payload())
            ->assertRedirect(route('admin.laws.index'));

        $article = Article::with('clauses')->findOrFail(500);
        $this->assertSame('Tội thử nghiệm kiểm định', $article->tieu_de);
        $this->assertNull($article->can_cu_boi_thuong, 'blank optional text is stored as NULL');
        $this->assertSame([1, 2], $article->clauses->pluck('so_khoan')->all());
        $this->assertSame(31.0, $article->clauses[1]->ti_le_ton_thuong_min);
        $this->assertNull($article->clauses[1]->ti_le_ton_thuong_max);

        $this->app->forgetInstance(ArticleRepository::class);
        $ids = array_column(app(LawSearch::class)->search('kiemdinhthunghiem', ''), 'id');
        $this->assertSame(500, $ids[0] ?? null);
        $this->get(route('law.show', 500))->assertOk();
    }

    public function test_a_new_chapter_name_creates_the_chapter(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.laws.store'), $this->payload(['chuong' => 'Chương thử nghiệm']))
            ->assertRedirect();

        $chapter = Chapter::firstWhere('ten_chuong', 'Chương thử nghiệm');
        $this->assertNotNull($chapter);
        $this->assertSame((int) Chapter::max('so_thu_tu'), (int) $chapter->so_thu_tu);
        $this->assertSame($chapter->id, Article::find(500)->chuong_id);
    }

    public function test_an_article_number_cannot_be_reused(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.laws.store'), $this->payload(['id' => 134]))
            ->assertSessionHasErrors('id');
    }

    public function test_invalid_input_is_rejected(): void
    {
        $admin = $this->admin();
        $cases = [
            'tieu_de' => ['tieu_de' => '  '],
            'khoan' => ['khoan' => []],
            'khoan.1.so_khoan' => ['khoan' => [['so_khoan' => 1, 'noi_dung' => 'a'], ['so_khoan' => 1, 'noi_dung' => 'b']]],
            'khoan.0.noi_dung' => ['khoan' => [['so_khoan' => 1, 'noi_dung' => '']]],
            'khoan.0.ti_le_ton_thuong_max' => ['khoan' => [['so_khoan' => 1, 'noi_dung' => 'a', 'ti_le_ton_thuong_min' => 40, 'ti_le_ton_thuong_max' => 30]]],
            'khoan.0.gia_tri_tai_san_min' => ['khoan' => [['so_khoan' => 1, 'noi_dung' => 'a', 'gia_tri_tai_san_min' => -1]]],
            'id' => ['id' => 'abc'],
        ];

        foreach ($cases as $field => $override) {
            $this->actingAs($admin)
                ->post(route('admin.laws.store'), $this->payload($override))
                ->assertSessionHasErrors($field);
        }

        $this->assertDatabaseMissing('dieu_luat', ['id' => 500]);
    }

    public function test_an_admin_can_edit_an_article_and_its_clauses(): void
    {
        $before = Clause::where('dieu_luat_id', 134)->pluck('so_khoan')->all();
        $this->assertGreaterThan(2, count($before));
        LawEmbedding::create(['dieu_luat_id' => 134, 'so_khoan' => 0, 'model' => 'm', 'content_hash' => str_repeat('a', 64), 'vector' => [0.1]]);
        LawEmbedding::create(['dieu_luat_id' => 134, 'so_khoan' => 1, 'model' => 'm', 'content_hash' => str_repeat('a', 64), 'vector' => [0.1]]);
        LawEmbedding::create(['dieu_luat_id' => 134, 'so_khoan' => 3, 'model' => 'm', 'content_hash' => str_repeat('a', 64), 'vector' => [0.1]]);

        $payload = $this->payload(['tieu_de' => 'Tội cố ý gây thương tích (đã sửa)']);
        unset($payload['id']);

        $this->actingAs($this->admin())->put(route('admin.laws.update', 134), $payload)
            ->assertRedirect(route('admin.laws.index'));

        $article = Article::with('clauses')->findOrFail(134);
        $this->assertSame('Tội cố ý gây thương tích (đã sửa)', $article->tieu_de);
        $this->assertSame([1, 2], $article->clauses->pluck('so_khoan')->all(), 'removed clauses are deleted');
        $this->assertSame('Mức nhẹ.', $article->clauses[0]->noi_dung);
        $this->assertSame([0, 1], LawEmbedding::where('dieu_luat_id', 134)->orderBy('so_khoan')->pluck('so_khoan')->all(),
            'vectors of removed clauses go, the rest wait for search:embed');
    }

    public function test_the_article_number_cannot_change_on_edit(): void
    {
        $this->actingAs($this->admin())->put(route('admin.laws.update', 134), $this->payload(['id' => 998]))
            ->assertSessionHasErrors('id');

        $this->assertDatabaseHas('dieu_luat', ['id' => 134]);
        $this->assertDatabaseMissing('dieu_luat', ['id' => 998]);
    }

    public function test_moving_the_last_article_out_of_a_chapter_removes_the_empty_chapter(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.laws.store'), $this->payload(['chuong' => 'Chương tạm']));
        $payload = $this->payload();
        unset($payload['id']);

        $this->actingAs($admin)->put(route('admin.laws.update', 500), $payload)->assertRedirect();

        $this->assertDatabaseMissing('chuong', ['ten_chuong' => 'Chương tạm']);
    }

    public function test_an_unused_article_can_be_deleted_with_its_clauses(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.laws.store'), $this->payload(['chuong' => 'Chương tạm']));

        $this->actingAs($admin)->delete(route('admin.laws.destroy', 500))
            ->assertRedirect(route('admin.laws.index'));

        $this->assertDatabaseMissing('dieu_luat', ['id' => 500]);
        $this->assertDatabaseMissing('khoan', ['dieu_luat_id' => 500]);
        $this->assertDatabaseMissing('chuong', ['ten_chuong' => 'Chương tạm']);
    }

    public function test_an_article_the_chatbot_already_answered_with_cannot_be_deleted(): void
    {
        $history = ChatHistory::create(['cau_hoi' => 'bị đánh', 'dieu_luat_id' => 134]);

        $this->actingAs($this->admin())->from(route('admin.laws.index'))
            ->delete(route('admin.laws.destroy', 134))
            ->assertRedirect(route('admin.laws.index'));

        $this->assertDatabaseHas('dieu_luat', ['id' => 134]);
        $this->assertSame(134, (int) $history->fresh()->dieu_luat_id);
    }
}
