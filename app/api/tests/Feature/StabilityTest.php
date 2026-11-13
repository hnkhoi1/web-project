<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Database\Seeders\LawSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class StabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->group(function () {
            Route::get('/__test/abort/{code}', fn (int $code) => abort($code));
            Route::get('/__test/boom', fn () => throw new RuntimeException('boom'));
        });
    }

    /** @return array<string, array{0: int}> */
    public static function friendlyErrors(): array
    {
        return ['forbidden' => [403], 'page expired' => [419], 'too many requests' => [429]];
    }

    #[DataProvider('friendlyErrors')]
    public function test_friendly_error_pages_for_browsers(int $status): void
    {
        $this->get('/__test/abort/'.$status)
            ->assertStatus($status)
            ->assertInertia(fn (Assert $page) => $page->component('errors/error')->where('status', $status));
    }

    public function test_a_server_error_shows_the_friendly_page_when_not_debugging(): void
    {
        config(['app.debug' => false]);

        $this->get('/__test/boom')
            ->assertStatus(500)
            ->assertInertia(fn (Assert $page) => $page->component('errors/error')->where('status', 500));
    }

    public function test_a_server_error_keeps_the_real_error_page_when_debugging(): void
    {
        config(['app.debug' => true]);

        // The debug page names the real exception; the friendly page never does.
        $this->get('/__test/boom')
            ->assertStatus(500)
            ->assertSee('RuntimeException', false)
            ->assertSee('boom');
    }

    public function test_errors_stay_json_for_api_style_requests(): void
    {
        $this->getJson('/__test/abort/419')->assertStatus(419)->assertJsonStructure(['message']);
    }

    public function test_unknown_pages_still_show_the_not_found_page(): void
    {
        $this->get('/khong-co-trang-nay')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page->component('errors/not-found'));
    }

    public function test_the_chatbot_is_rate_limited_with_a_vietnamese_message(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($user)->postJson(route('chat.ask'), ['message' => 'xyz'])->assertOk();
        }

        $this->actingAs($user)->postJson(route('chat.ask'), ['message' => 'xyz'])
            ->assertStatus(429)
            ->assertJson(['message' => 'Bạn thao tác quá nhanh. Vui lòng đợi một chút rồi thử lại.'])
            ->assertHeader('Retry-After');
    }

    public function test_the_chat_limit_is_per_user(): void
    {
        $busy = User::factory()->create();
        $other = User::factory()->create();

        for ($i = 0; $i < 21; $i++) {
            $this->actingAs($busy)->postJson(route('chat.ask'), ['message' => 'xyz']);
        }

        $this->actingAs($other)->postJson(route('chat.ask'), ['message' => 'xyz'])->assertOk();
    }

    public function test_init_data_seeds_an_empty_database(): void
    {
        $this->assertSame(0, Article::count());

        $this->artisan('app:init-data')->assertSuccessful();

        $this->assertSame(18, Article::count());
        $this->assertDatabaseHas('users', ['username' => 'demo', 'is_demo' => true]);
    }

    public function test_init_data_leaves_existing_data_alone(): void
    {
        $this->seed(LawSeeder::class);
        $count = Article::count();

        $this->artisan('app:init-data')
            ->expectsOutputToContain('đã có')
            ->assertSuccessful();

        $this->assertSame($count, Article::count());
        $this->assertDatabaseMissing('users', ['username' => 'demo']);
    }
}
