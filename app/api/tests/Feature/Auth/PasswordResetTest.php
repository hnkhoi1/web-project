<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_PASSWORD = 'Mat-khau-moi-12345!';

    public function test_forgot_password_screen_renders_for_guests(): void
    {
        $this->get(route('password.request'))->assertOk();
    }

    public function test_signed_in_users_are_sent_away_from_the_reset_screens(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('password.request'))
            ->assertRedirect();
    }

    public function test_link_is_emailed_to_an_existing_account(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'nguoi.dung@example.com']);

        $this->post(route('password.email'), ['email' => '  Nguoi.Dung@Example.com '])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_email_is_in_vietnamese_and_links_to_the_reset_page(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use ($user): bool {
            $mail = $n->toMail($user);

            return str_starts_with($mail->subject, 'Đặt lại mật khẩu')
                && str_contains($mail->actionUrl, '/dat-lai-mat-khau/'.$n->token)
                && str_contains($mail->actionUrl, 'email='.urlencode($user->email))
                && $mail->actionText === 'Đặt lại mật khẩu';
        });
    }

    public function test_unknown_email_gets_the_same_answer_and_no_mail(): void
    {
        Notification::fake();
        $known = User::factory()->create();

        $found = $this->post(route('password.email'), ['email' => $known->email])->getSession()->get('status');
        $missing = $this->post(route('password.email'), ['email' => 'khong.co@example.com'])
            ->assertSessionHasNoErrors()
            ->getSession()->get('status');

        $this->assertSame($found, $missing);
        Notification::assertSentToTimes($known, ResetPassword::class, 1);
        Notification::assertCount(1);
    }

    public function test_the_demo_account_never_receives_a_reset_link(): void
    {
        Notification::fake();
        $demo = User::factory()->create(['is_demo' => true]);

        $this->post(route('password.email'), ['email' => $demo->email])->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_email_is_required_and_must_be_valid(): void
    {
        $this->post(route('password.email'), ['email' => ''])->assertSessionHasErrors('email');
        $this->post(route('password.email'), ['email' => 'khong-phai-email'])->assertSessionHasErrors('email');
    }

    public function test_reset_screen_renders_with_token_and_email(): void
    {
        $this->get(route('password.reset', ['token' => 'abc', 'email' => 'a@example.com']))->assertOk();
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        $this->post(route('login.store'), ['login' => $user->email, 'password' => self::NEW_PASSWORD]);
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_old_password_stops_working_after_a_reset(): void
    {
        $user = User::factory()->create();
        $this->post(route('password.update'), [
            'token' => Password::createToken($user),
            'email' => $user->email,
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ]);

        $this->post(route('login.store'), ['login' => $user->email, 'password' => 'password']);

        $this->assertGuest();
    }

    public function test_a_reset_signs_the_account_out_of_other_sessions(): void
    {
        $user = User::factory()->create();
        DB::table('sessions')->insert([
            'id' => 'old-session',
            'user_id' => $user->id,
            'payload' => '',
            'last_activity' => time(),
        ]);

        $this->post(route('password.update'), [
            'token' => Password::createToken($user),
            'email' => $user->email,
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ]);

        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
    }

    public function test_a_token_works_only_once(): void
    {
        $user = User::factory()->create();
        $payload = [
            'token' => Password::createToken($user),
            'email' => $user->email,
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ];

        $this->post(route('password.update'), $payload)->assertRedirect(route('login'));
        $this->post(route('password.update'), $payload)->assertSessionHasErrors('email');
    }

    public function test_an_invalid_token_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.update'), [
            'token' => 'khong-hop-le',
            'email' => $user->email,
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertSessionHasErrors('email');

        $this->assertTrue(\Hash::check('password', $user->fresh()->password));
    }

    public function test_an_expired_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $this->travel(61)->minutes();

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertSessionHasErrors('email');
    }

    public function test_the_new_password_must_be_confirmed_and_strong_enough(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => 'khac-hoan-toan',
        ])->assertSessionHasErrors('password');

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => '123',
            'password_confirmation' => '123',
        ])->assertSessionHasErrors('password');
    }
}
