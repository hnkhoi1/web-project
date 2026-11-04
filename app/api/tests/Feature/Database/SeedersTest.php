<?php

namespace Tests\Feature\Database;

use App\Models\Article;
use App\Models\Chapter;
use App\Models\Note;
use App\Models\User;
use App\Models\UserPref;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_running_twice(): void
    {
        $this->seed(DatabaseSeeder::class);
        $chapters = Chapter::count();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(18, Article::count());
        $this->assertSame(1, User::where('is_demo', true)->count());
        $this->assertSame($chapters, Chapter::count());
    }

    public function test_rerun_preserves_demo(): void
    {
        $this->seed(DatabaseSeeder::class);
        $demo = User::where('username', 'demo')->firstOrFail();
        UserPref::create(['user_id' => $demo->id, 'theme' => 'dark']);
        $demo->notes()->create(['title' => 'Ghi chú', 'color' => 'rose']);
        $hash = $demo->password;

        $this->seed(DatabaseSeeder::class);

        $demo->refresh();
        $this->assertSame($hash, $demo->password);
        $this->assertSame('dark', $demo->prefs->theme);
        $this->assertSame(1, Note::where('user_id', $demo->id)->count());
    }
}
