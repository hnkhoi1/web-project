<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RecoveryRemovedTest extends TestCase
{
    use RefreshDatabase;

    public function test_removed_routes_return_404(): void
    {
        foreach (['/forgot-password', '/reset-password/x', '/email/verify', '/user/confirm-password'] as $uri) {
            $this->get($uri)->assertNotFound();
        }
    }

    public function test_no_passkeys_table_or_two_factor_columns(): void
    {
        $this->assertFalse(Schema::hasTable('passkeys'));
        $this->assertFalse(Schema::hasColumn('users', 'two_factor_secret'));
    }
}
