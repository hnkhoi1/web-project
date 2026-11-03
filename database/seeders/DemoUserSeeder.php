<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    /**
     * Create the shared demo account (idempotent, bypasses registration rules).
     */
    public function run(): void
    {
        User::unguarded(fn () => User::firstOrCreate(
            ['username' => 'demo'],
            [
                'email' => 'demo@demo.local',
                'name' => 'Tài khoản Demo',
                'password' => '12345',
                'is_demo' => true,
            ],
        ));
    }
}
