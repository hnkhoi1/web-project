<?php

namespace Tests\Feature;

use App\Models\ChatHistory;
use App\Models\Contact;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\LawSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: string}> */
    public static function adminPages(): array
    {
        return [
            'overview' => ['admin.index'],
            'contacts' => ['admin.contacts.index'],
            'questions' => ['admin.questions.index'],
            'users' => ['admin.users.index'],
        ];
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->role = User::ROLE_ADMIN;
        $user->save();

        return $user;
    }

    private function contact(array $attrs = []): Contact
    {
        return Contact::create(array_merge([
            'ho_ten' => 'Nguyễn Văn A',
            'email' => 'a@example.com',
            'noi_dung' => 'Xin chào',
            'trang_thai' => 'moi',
        ], $attrs));
    }

    #[DataProvider('adminPages')]
    public function test_guests_are_sent_to_login(string $route): void
    {
        $this->get(route($route))->assertRedirect('/login');
    }

    #[DataProvider('adminPages')]
    public function test_regular_users_get_a_404(string $route): void
    {
        $this->actingAs(User::factory()->create())->get(route($route))->assertNotFound();
    }

    #[DataProvider('adminPages')]
    public function test_the_demo_account_cannot_get_in(string $route): void
    {
        $this->actingAs(User::factory()->demo()->create())->get(route($route))->assertNotFound();
    }

    #[DataProvider('adminPages')]
    public function test_admins_can_open_every_page(string $route): void
    {
        $this->actingAs($this->admin())->get(route($route))->assertOk();
    }

    public function test_the_admin_flag_is_shared_with_the_frontend(): void
    {
        $this->actingAs($this->admin())->get(route('admin.index'))
            ->assertInertia(fn (Assert $page) => $page->where('auth.user.isAdmin', true));

        $this->actingAs(User::factory()->create())->get(route('notes.index'))
            ->assertInertia(fn (Assert $page) => $page->where('auth.user.isAdmin', false));
    }

    public function test_overview_counts_questions_and_misses(): void
    {
        $this->seed(LawSeeder::class);
        $user = User::factory()->create();
        ChatHistory::create(['user_id' => $user->id, 'cau_hoi' => 'trộm xe', 'dieu_luat_id' => 173, 'khoan_so' => 1, 'diem' => 0.9]);
        ChatHistory::create(['user_id' => $user->id, 'cau_hoi' => 'trộm điện thoại', 'dieu_luat_id' => 173, 'khoan_so' => 1, 'diem' => 0.8]);
        ChatHistory::create(['user_id' => $user->id, 'cau_hoi' => 'câu lạ', 'dieu_luat_id' => null]);
        $this->contact();

        $this->actingAs($this->admin())->get(route('admin.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/index')
                ->where('stats.questions', 3)
                ->where('stats.missed', 1)
                ->where('stats.missedRate', 33)
                ->where('stats.questionsToday', 3)
                ->where('stats.newContacts', 1)
                ->where('stats.users', 2)
                ->has('chart', 14)
                ->where('chart.13.total', 3)
                ->where('chart.13.missed', 1)
                ->where('topArticles.0.id', 173)
                ->where('topArticles.0.total', 2));
    }

    public function test_overview_works_with_no_data(): void
    {
        $this->actingAs($this->admin())->get(route('admin.index'))
            ->assertInertia(fn (Assert $page) => $page->where('stats.missedRate', 0)->has('chart', 14)->where('topArticles', []));
    }

    public function test_contacts_can_be_filtered_by_status(): void
    {
        $this->contact(['ho_ten' => 'Chưa trả lời']);
        $this->contact(['ho_ten' => 'Đã trả lời', 'trang_thai' => 'da_tra_loi']);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.contacts.index'))
            ->assertInertia(fn (Assert $page) => $page->has('contacts.data', 2));

        $this->actingAs($admin)->get(route('admin.contacts.index', ['trang_thai' => 'moi']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('contacts.data', 1)
                ->where('contacts.data.0.ho_ten', 'Chưa trả lời'));
    }

    public function test_an_admin_can_mark_a_contact_answered(): void
    {
        $contact = $this->contact();

        $this->actingAs($this->admin())
            ->patch(route('admin.contacts.update', $contact), ['trang_thai' => 'da_tra_loi'])
            ->assertRedirect();

        $this->assertSame('da_tra_loi', $contact->fresh()->trang_thai);
    }

    public function test_a_contact_status_must_be_valid(): void
    {
        $contact = $this->contact();

        $this->actingAs($this->admin())
            ->patch(route('admin.contacts.update', $contact), ['trang_thai' => 'bay-ba'])
            ->assertSessionHasErrors('trang_thai');

        $this->assertSame('moi', $contact->fresh()->trang_thai);
    }

    public function test_regular_users_cannot_change_a_contact(): void
    {
        $contact = $this->contact();

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.contacts.update', $contact), ['trang_thai' => 'da_tra_loi'])
            ->assertNotFound();

        $this->assertSame('moi', $contact->fresh()->trang_thai);
    }

    public function test_questions_can_be_limited_to_unmatched_ones_and_searched(): void
    {
        $this->seed(LawSeeder::class);
        ChatHistory::create(['cau_hoi' => 'trộm xe máy', 'dieu_luat_id' => 173]);
        ChatHistory::create(['cau_hoi' => 'câu hỏi lạ một', 'dieu_luat_id' => null]);
        ChatHistory::create(['cau_hoi' => 'câu hỏi lạ hai', 'dieu_luat_id' => null]);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.questions.index'))
            ->assertInertia(fn (Assert $page) => $page->has('questions.data', 3));

        $this->actingAs($admin)->get(route('admin.questions.index', ['loai' => 'khong-khop']))
            ->assertInertia(fn (Assert $page) => $page->has('questions.data', 2)->where('filters.loai', 'khong-khop'));

        $this->actingAs($admin)->get(route('admin.questions.index', ['q' => 'xe máy']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('questions.data', 1)
                ->where('questions.data.0.dieu_luat_id', 173)
                ->where('questions.data.0.tieu_de', fn ($t) => str_contains($t, 'trộm')));
    }

    public function test_a_search_with_like_wildcards_is_treated_literally(): void
    {
        ChatHistory::create(['cau_hoi' => 'giảm 50% mức phạt']);
        ChatHistory::create(['cau_hoi' => 'một câu khác']);

        $this->actingAs($this->admin())->get(route('admin.questions.index', ['q' => '%']))
            ->assertInertia(fn (Assert $page) => $page->has('questions.data', 1));
    }

    public function test_users_can_be_searched_and_never_expose_passwords(): void
    {
        User::factory()->create(['name' => 'Trần Thị Bình', 'email' => 'binh@example.com']);
        User::factory()->create(['name' => 'Lê Văn Cường', 'email' => 'cuong@example.com']);

        $this->actingAs($this->admin())->get(route('admin.users.index', ['q' => 'binh']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.email', 'binh@example.com')
                ->missing('users.data.0.password')
                ->missing('users.data.0.remember_token'));
    }

    public function test_the_role_cannot_be_mass_assigned(): void
    {
        $user = User::create(['name' => 'X', 'email' => 'x@example.com', 'password' => 'Mat-khau-12345!', 'role' => 'admin']);

        $this->assertFalse($user->fresh()->isAdmin());
    }

    public function test_registering_with_a_role_field_does_not_make_an_admin(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Kẻ gian',
            'email' => 'kegian@example.com',
            'password' => 'Mat-khau-12345!',
            'password_confirmation' => 'Mat-khau-12345!',
            'role' => 'admin',
        ]);

        $this->assertFalse(User::where('email', 'kegian@example.com')->firstOrFail()->isAdmin());
    }

    public function test_promote_command_grants_and_revokes_admin(): void
    {
        $user = User::factory()->create(['email' => 'sep@example.com']);

        $this->artisan('admin:promote', ['login' => 'SEP@example.com'])->assertSuccessful();
        $this->assertTrue($user->fresh()->isAdmin());

        $this->artisan('admin:promote', ['login' => 'sep@example.com', '--revoke' => true])->assertSuccessful();
        $this->assertFalse($user->fresh()->isAdmin());
    }

    public function test_promote_command_refuses_unknown_and_demo_accounts(): void
    {
        $demo = User::factory()->demo()->create();

        $this->artisan('admin:promote', ['login' => 'khong.co@example.com'])->assertFailed();
        $this->artisan('admin:promote', ['login' => $demo->email])->assertFailed();
        $this->assertFalse($demo->fresh()->isAdmin());
    }

    public function test_admin_seeder_promotes_only_the_configured_email(): void
    {
        $boss = User::factory()->create(['email' => 'boss@example.com']);
        $other = User::factory()->create();
        config(['app.admin_email' => 'Boss@Example.com']);

        $this->seed(AdminSeeder::class);

        $this->assertTrue($boss->fresh()->isAdmin());
        $this->assertFalse($other->fresh()->isAdmin());
    }

    public function test_admin_seeder_does_nothing_without_a_matching_account(): void
    {
        config(['app.admin_email' => 'chua.dang.ky@example.com']);
        $this->seed(AdminSeeder::class);

        $this->assertSame(0, User::where('role', User::ROLE_ADMIN)->count());

        config(['app.admin_email' => null]);
        $this->seed(AdminSeeder::class);
        $this->assertSame(0, User::where('role', User::ROLE_ADMIN)->count());
    }
}
