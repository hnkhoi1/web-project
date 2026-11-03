<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use App\Models\UserPref;
use Database\Seeders\LawSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotesTest extends TestCase
{
    use RefreshDatabase;

    private function note(User $user, array $attrs = []): Note
    {
        $note = new Note(array_merge(['title' => 'T', 'content' => 'C', 'color' => 'yellow', 'pinned' => false], $attrs));
        $note->user_id = $user->id;
        $note->save();

        return $note;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('notes.index'))->assertRedirect('/login');
        $this->post(route('notes.store'), ['title' => 'x'])->assertRedirect('/login');
    }

    public function test_list_is_pinned_first_then_newest_updated_and_only_own(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $old = $this->note($user, ['title' => 'old']);
        $new = $this->note($user, ['title' => 'new']);
        $pinned = $this->note($user, ['title' => 'pinned', 'pinned' => true]);
        $this->note($other, ['title' => 'theirs']);
        Note::whereKey($old->id)->update(['updated_at' => now()->subDays(2)]);
        Note::whereKey($new->id)->update(['updated_at' => now()->subDay()]);
        Note::whereKey($pinned->id)->update(['updated_at' => now()->subDays(5)]);

        $this->actingAs($user)->get(route('notes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('notes/index')
                ->where('notes.0.title', 'pinned')
                ->where('notes.1.title', 'new')
                ->where('notes.2.title', 'old')
                ->has('notes', 3));
    }

    public function test_popular_articles_are_the_six_original_ones_in_order(): void
    {
        $this->seed(LawSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('notes.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('popular', 6)
                ->where('popular.0.id', 123)
                ->where('popular.1.id', 168)
                ->where('popular.2.id', 174)
                ->where('popular.3.id', 260)
                ->where('popular.4.id', 353)
                ->where('popular.5.id', 134)
                ->has('popular.0.tieu_de')
                ->has('popular.0.tom_tat')
                ->has('popular.0.khung_hinh_phat'));
    }

    public function test_store_creates_note_with_pref_color_by_default(): void
    {
        $user = User::factory()->create();
        UserPref::create(['user_id' => $user->id, 'theme' => 'system', 'font' => 'md', 'note_color' => 'rose']);

        $this->actingAs($user)->post(route('notes.store'), ['title' => 'Hello', 'content' => ''])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('notes', ['user_id' => $user->id, 'title' => 'Hello', 'color' => 'rose', 'pinned' => 0]);
    }

    public function test_store_with_only_content_derives_title(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('notes.store'), ['content' => str_repeat('a', 100)])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('notes', ['user_id' => $user->id, 'title' => str_repeat('a', 40)]);
    }

    public function test_store_with_source_answers_json_when_requested(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('notes.store'), [
            'title' => 'Từ chat', 'source_type' => 'chat', 'source_ref' => 'abc',
        ])->assertCreated()->assertJsonPath('note.source_type', 'chat');

        $this->actingAs($user)->postJson(route('notes.store'), [])->assertStatus(422);
        $this->actingAs($user)->postJson(route('notes.store'), ['title' => 'x', 'source_type' => 'bogus'])
            ->assertStatus(422)->assertJsonValidationErrors('source_type');
    }

    public function test_validation_rules(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('notes.store'), ['title' => '', 'content' => ''])->assertSessionHasErrors('title');
        $this->post(route('notes.store'), ['title' => str_repeat('a', 121)])->assertSessionHasErrors('title');
        $this->post(route('notes.store'), ['title' => 'ok', 'content' => str_repeat('a', 5001)])->assertSessionHasErrors('content');
        $this->post(route('notes.store'), ['title' => 'ok', 'color' => 'red'])->assertSessionHasErrors('color');
        $this->assertDatabaseCount('notes', 0);

        $this->post(route('notes.store'), ['title' => str_repeat('a', 120), 'content' => str_repeat('b', 5000)])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('notes', 1);
    }

    public function test_note_limit_is_200_and_update_still_works_at_limit(): void
    {
        $user = User::factory()->create();
        $rows = [];
        for ($i = 0; $i < 200; $i++) {
            $rows[] = ['user_id' => $user->id, 'title' => "n$i", 'color' => 'yellow', 'pinned' => false, 'created_at' => now(), 'updated_at' => now()];
        }
        Note::insert($rows);

        $this->actingAs($user)->post(route('notes.store'), ['title' => 'one too many'])
            ->assertSessionHasErrors(['title' => 'Bạn đã đạt tối đa 200 ghi chú.']);
        $this->assertSame(200, Note::where('user_id', $user->id)->count());

        $first = Note::where('user_id', $user->id)->first();
        $this->patch(route('notes.update', $first), ['title' => 'edited', 'color' => 'blue', 'pinned' => true])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('notes', ['id' => $first->id, 'title' => 'edited', 'color' => 'blue', 'pinned' => 1]);
    }

    public function test_pin_toggle_and_destroy(): void
    {
        $user = User::factory()->create();
        $note = $this->note($user);

        $this->actingAs($user)->patch(route('notes.pin', $note), ['pinned' => true])->assertSessionHasNoErrors();
        $this->assertTrue($note->fresh()->pinned);
        $this->patch(route('notes.pin', $note), ['pinned' => false]);
        $this->assertFalse($note->fresh()->pinned);

        $this->delete(route('notes.destroy', $note))->assertSessionHasNoErrors();
        $this->assertModelMissing($note);
    }

    public function test_other_users_cannot_touch_a_note(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $note = $this->note($owner, ['title' => 'mine']);

        $this->actingAs($intruder);
        $this->patch(route('notes.update', $note), ['title' => 'hacked'])->assertForbidden();
        $this->patch(route('notes.pin', $note), ['pinned' => true])->assertForbidden();
        $this->delete(route('notes.destroy', $note))->assertForbidden();

        $fresh = $note->fresh();
        $this->assertSame('mine', $fresh->title);
        $this->assertFalse($fresh->pinned);
    }

    public function test_script_content_is_returned_verbatim(): void
    {
        $user = User::factory()->create();
        $this->note($user, ['title' => 'x', 'content' => '<script>alert(1)</script>']);

        $this->actingAs($user)->get(route('notes.index'))
            ->assertInertia(fn (Assert $page) => $page->where('notes.0.content', '<script>alert(1)</script>'));
    }
}
