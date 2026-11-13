<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AdminPromote extends Command
{
    protected $signature = 'admin:promote {login : Email or username of an existing account} {--revoke : Remove admin rights instead}';

    protected $description = 'Give an existing account admin rights (or take them away with --revoke)';

    public function handle(): int
    {
        $login = mb_strtolower(trim((string) $this->argument('login')));
        $user = User::where('email', $login)->orWhere('username', $login)->first();

        if ($user === null) {
            $this->error("Không tìm thấy tài khoản \"{$login}\". Tài khoản phải đăng ký trước.");

            return self::FAILURE;
        }

        if ($user->isDemo()) {
            $this->error('Tài khoản demo dùng chung nên không thể làm admin.');

            return self::FAILURE;
        }

        $user->role = $this->option('revoke') ? User::ROLE_USER : User::ROLE_ADMIN;
        $user->save();

        $this->info($user->isAdmin()
            ? "{$user->email} giờ là admin."
            : "{$user->email} không còn là admin.");

        return self::SUCCESS;
    }
}
