<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegistrationRulesTest extends TestCase
{
    use RefreshDatabase;

    /** @param array<string, string> $overrides */
    private function register(array $overrides = [])
    {
        return $this->post('/register', [
            'name' => 'Người Dùng',
            'email' => 'a@x.com',
            'password' => 'abc12345',
            'password_confirmation' => 'abc12345',
            ...$overrides,
        ]);
    }

    public function test_registers_and_logs_in_with_lowercased_email(): void
    {
        $this->register(['email' => 'A@X.com'])->assertRedirect('/ghi-chu');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'a@x.com']);
    }

    public function test_rejects_duplicate_email_regardless_of_case(): void
    {
        User::factory()->create(['email' => 'a@x.com']);

        $this->register(['email' => 'A@X.COM'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @return array<string, array{string, string}> */
    public static function invalidFields(): array
    {
        return [
            'name too short' => ['name', 'A'],
            'name too long' => ['name', str_repeat('a', 41)],
            'name control char' => ['name', "Ab\x07c"],
            'password letters only' => ['password', 'abcdefgh'],
            'password digits only' => ['password', '12345678'],
            'password too short' => ['password', 'abc1234'],
            'password too long' => ['password', str_repeat('a1', 36).'a'],
        ];
    }

    #[DataProvider('invalidFields')]
    public function test_rejects_invalid_input(string $field, string $value): void
    {
        $overrides = [$field => $value];
        if ($field === 'password') {
            $overrides['password_confirmation'] = $value;
        }

        $this->register($overrides)->assertSessionHasErrors($field);
        $this->assertGuest();
    }
}
