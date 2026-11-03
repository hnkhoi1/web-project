<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_preferences_persist_and_are_shared(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('preferences.edit'))
            ->put(route('preferences.update'), ['theme' => 'dark', 'font' => 'lg', 'noteColor' => 'rose'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_prefs', [
            'user_id' => $user->id, 'theme' => 'dark', 'font' => 'lg', 'note_color' => 'rose',
        ]);

        // The same in-memory user served both requests; a real request starts fresh.
        $this->actingAs($user->fresh())->get(route('preferences.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/preferences')
                ->where('prefs', ['theme' => 'dark', 'font' => 'lg', 'noteColor' => 'rose'])
                ->where('auth.user.id', $user->id)
                ->where('auth.user.isDemo', false)
                ->has('auth.user.username')
                ->has('auth.user.avatar')
                ->missing('auth.user.password'));
    }

    public function test_second_save_updates_the_same_row(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->put(route('preferences.update'), ['theme' => 'dark', 'font' => 'lg', 'noteColor' => 'rose']);
        $this->actingAs($user)->put(route('preferences.update'), ['theme' => 'light', 'font' => 'sm', 'noteColor' => 'gray']);

        $this->assertDatabaseCount('user_prefs', 1);
        $this->assertSame('light', $user->fresh()->prefs->theme);
    }

    /** @return array<string, array{0: array<string, string>, 1: string}> */
    public static function invalidProvider(): array
    {
        $ok = ['theme' => 'dark', 'font' => 'lg', 'noteColor' => 'rose'];

        return [
            'theme' => [['theme' => 'purple'] + $ok, 'theme'],
            'font' => [['font' => 'huge'] + $ok, 'font'],
            'color' => [['noteColor' => 'black'] + $ok, 'noteColor'],
        ];
    }

    /** @param array<string, string> $data */
    #[DataProvider('invalidProvider')]
    public function test_invalid_values_are_rejected(array $data, string $field): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('preferences.edit'))
            ->put(route('preferences.update'), $data)
            ->assertSessionHasErrors($field);

        $this->assertDatabaseCount('user_prefs', 0);
    }

    public function test_defaults_when_no_row(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('preferences.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('prefs', ['theme' => 'system', 'font' => 'md', 'noteColor' => 'yellow']));
    }

    public function test_guest_gets_defaults(): void
    {
        $this->get(route('login'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('prefs', ['theme' => 'system', 'font' => 'md', 'noteColor' => 'yellow'])
                ->where('auth.user', null));
    }

    public function test_root_element_renders_dark_class_and_font(): void
    {
        $user = User::factory()->create();
        $user->prefs()->create(['theme' => 'dark', 'font' => 'lg', 'note_color' => 'rose']);

        $html = $this->actingAs($user)->get(route('profile.edit'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<html[^>]*class="[^"]*\bdark\b[^"]*"/', $html);
        $this->assertMatchesRegularExpression('/<html[^>]*data-font="lg"/', $html);
        $this->assertMatchesRegularExpression('/<html[^>]*data-note-color="rose"/', $html);
    }

    public function test_light_user_does_not_get_dark_class(): void
    {
        $user = User::factory()->create();
        $user->prefs()->create(['theme' => 'light', 'font' => 'md', 'note_color' => 'yellow']);

        $html = $this->actingAs($user)->get(route('profile.edit'))->getContent();

        $this->assertDoesNotMatchRegularExpression('/<html[^>]*class="[^"]*\bdark\b/', $html);
        $this->assertMatchesRegularExpression('/<html[^>]*data-font="md"/', $html);
    }
}
