<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Auth\NormalizedLoginRateLimiter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter;
use Normalizer;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Fortify::ignoreRoutes();

        $this->app->singleton(LoginRateLimiter::class, NormalizedLoginRateLimiter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureAuthentication();
    }

    /**
     * Normalize a login identifier: NFC, trimmed, lowercase.
     */
    public static function normalizeIdentifier(mixed $value): string
    {
        $value = is_string($value) ? $value : '';
        $normalized = Normalizer::normalize($value, Normalizer::FORM_C);

        return mb_strtolower(trim($normalized === false ? $value : $normalized));
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Log in by username or email.
     */
    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $identifier = self::normalizeIdentifier($request->input(Fortify::username()));

            if ($identifier === '') {
                return null;
            }

            $user = User::where('email', $identifier)
                ->orWhere('username', $identifier)
                ->first();

            return $user && Hash::check((string) $request->input('password'), $user->password)
                ? $user
                : null;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/login', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));
    }
}
