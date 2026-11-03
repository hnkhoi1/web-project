<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Normalizer;
use Tests\TestCase;

class LoginIdentifierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoUserSeeder::class);
    }

    public function test_logs_in_by_username(): void
    {
        $this->post('/login', ['login' => 'demo', 'password' => '12345'])
            ->assertRedirect('/ghi-chu');

        $this->assertAuthenticated();
    }

    public function test_username_ignores_case_and_spaces(): void
    {
        $this->post('/login', ['login' => 'DEMO ', 'password' => '12345']);

        $this->assertAuthenticated();
    }

    public function test_logs_in_by_email_case_insensitive(): void
    {
        $this->post('/login', ['login' => 'Demo@Demo.local', 'password' => '12345']);

        $this->assertAuthenticated();
    }

    public function test_wrong_password_rejected(): void
    {
        $this->post('/login', ['login' => 'demo', 'password' => 'sai'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_locked_after_five_failures(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['login' => 'demo', 'password' => 'sai']);
        }

        $this->post('/login', ['login' => 'demo', 'password' => '12345'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_decomposed_username_matches(): void
    {
        User::factory()->create(['username' => 'tuấn', 'password' => 'abc12345']);

        $this->post('/login', [
            'login' => Normalizer::normalize('tuấn', Normalizer::FORM_D),
            'password' => 'abc12345',
        ]);

        $this->assertAuthenticated();
    }
}
