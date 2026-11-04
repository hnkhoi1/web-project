<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_users_are_sent_to_notes(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect(route('notes.index'));
    }
}
