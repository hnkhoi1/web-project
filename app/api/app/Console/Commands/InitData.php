<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;

class InitData extends Command
{
    protected $signature = 'app:init-data';

    protected $description = 'Seed the law data and the demo account on a fresh database; does nothing once the law data exists';

    public function handle(): int
    {
        if (Article::query()->exists()) {
            $this->line('Dữ liệu luật đã có, bỏ qua.');

            return self::SUCCESS;
        }

        $this->info('CSDL trống: nạp dữ liệu luật và tài khoản demo...');

        return $this->call('db:seed', ['--force' => true]);
    }
}
