<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_open_public_pages(): void
    {
        foreach (['/gioi-thieu', '/lien-he', '/login', '/register'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_guests_are_sent_to_login_for_private_pages(): void
    {
        foreach (['/ghi-chu', '/tra-cuu', '/dieu-luat/134', '/tu-van', '/ho-so', '/doi-mat-khau', '/tuy-chinh'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_home_redirects_by_auth_state(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect('/ghi-chu');
    }

    public function test_unknown_url_renders_vietnamese_404_page(): void
    {
        foreach ([false, true] as $login) {
            if ($login) {
                $this->actingAs(User::factory()->create());
            }

            $this->get('/khong-ton-tai')
                ->assertNotFound()
                ->assertInertia(fn ($page) => $page->component('errors/not-found'));
        }
    }
}
