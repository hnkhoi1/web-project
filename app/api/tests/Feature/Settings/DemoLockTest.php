<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoLockTest extends TestCase
{
    use RefreshDatabase;

    private function demo(): User
    {
        return User::factory()->demo()->create([
            'name' => 'Demo',
            'password' => '12345',
            'avatar' => 'data:image/jpeg;base64,AAAA',
        ]);
    }

    public function test_demo_cannot_update_profile(): void
    {
        $user = $this->demo();

        $this->actingAs($user)->from(route('profile.edit'))
            ->patch(route('profile.update'), ['name' => 'X'])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('demo');

        $this->assertSame('Demo', $user->fresh()->name);
    }

    public function test_demo_cannot_change_avatar(): void
    {
        $user = $this->demo();

        $this->actingAs($user)->from(route('profile.edit'))
            ->post(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('a.png', 200, 200)])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('demo');

        $this->assertSame('data:image/jpeg;base64,AAAA', $user->fresh()->avatar);
    }

    public function test_demo_cannot_remove_avatar(): void
    {
        $user = $this->demo();

        $this->actingAs($user)->from(route('profile.edit'))
            ->delete(route('profile.avatar.destroy'))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('demo');

        $this->assertSame('data:image/jpeg;base64,AAAA', $user->fresh()->avatar);
    }

    public function test_demo_cannot_change_password(): void
    {
        $user = $this->demo();

        $this->actingAs($user)->from(route('security.edit'))
            ->put(route('user-password.update'), [
                'current_password' => '12345',
                'password' => 'new-pass123',
                'password_confirmation' => 'new-pass123',
            ])
            ->assertRedirect(route('security.edit'))
            ->assertSessionHasErrors('demo');

        $this->assertTrue(Hash::check('12345', $user->fresh()->password));
    }

    public function test_demo_cannot_delete_account(): void
    {
        $user = $this->demo();

        $this->actingAs($user)->from(route('profile.edit'))
            ->delete(route('profile.destroy'), ['password' => '12345'])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('demo');

        $this->assertNotNull($user->fresh());
    }

    public function test_demo_can_save_prefs(): void
    {
        $user = $this->demo();

        $this->actingAs($user)->from(route('preferences.edit'))
            ->put(route('preferences.update'), ['theme' => 'dark', 'font' => 'lg', 'noteColor' => 'rose'])
            ->assertSessionHasNoErrors();

        $this->assertSame('dark', $user->fresh()->prefs->theme);
    }

    public function test_regular_user_can_change_password_and_delete(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('security.edit'))
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'new-pass123',
                'password_confirmation' => 'new-pass123',
            ])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('new-pass123', $user->fresh()->password));

        $user->notes()->create(['title' => 'a', 'content' => 'b']);
        $contactId = DB::table('contacts')->insertGetId([
            'user_id' => $user->id, 'ho_ten' => 'Nguyen', 'email' => 'a@b.vn', 'noi_dung' => 'Xin chào bạn nhé',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $historyId = DB::table('lich_su_hoi_dap')->insertGetId([
            'user_id' => $user->id, 'cau_hoi' => 'q',
        ]);

        $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'new-pass123'])
            ->assertSessionHasNoErrors();

        $this->assertNull(User::find($user->id));
        $this->assertSame(0, DB::table('notes')->count());
        $this->assertNull(DB::table('contacts')->where('id', $contactId)->value('user_id'));
        $this->assertNull(DB::table('lich_su_hoi_dap')->where('id', $historyId)->value('user_id'));
        $this->assertNotNull(DB::table('contacts')->where('id', $contactId)->first());
    }

    public function test_email_cannot_be_changed_via_profile(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com', 'name' => 'Cũ Tên']);

        $this->actingAs($user)->patch(route('profile.update'), ['name' => 'Tên Mới', 'email' => 'new@x.com'])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('old@example.com', $user->email);
        $this->assertSame('Tên Mới', $user->name);
    }
}
