<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Promotes the account whose email is in ADMIN_EMAIL (.env). The account must already exist:
 * register it first, then run `php artisan db:seed --class=AdminSeeder` (or `admin:promote <email>`).
 * No account is ever created here and no password is stored in the environment.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = mb_strtolower(trim((string) config('app.admin_email')));

        if ($email === '') {
            return;
        }

        $user = User::where('email', $email)->where('is_demo', false)->first();

        if ($user === null) {
            $this->command?->warn("ADMIN_EMAIL={$email}: chưa có tài khoản này. Hãy đăng ký trước rồi chạy lại.");

            return;
        }

        $user->role = User::ROLE_ADMIN;
        $user->save();
    }
}
