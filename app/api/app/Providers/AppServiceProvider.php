<?php

namespace App\Providers;

use App\Search\ArticleRepository;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ArticleRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureResetMail();
    }

    /**
     * Vietnamese wording for the password-reset email.
     */
    protected function configureResetMail(): void
    {
        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
            $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject('Đặt lại mật khẩu - '.config('app.name'))
                ->greeting('Xin chào!')
                ->line('Bạn nhận được thư này vì có yêu cầu đặt lại mật khẩu cho tài khoản của bạn.')
                ->action('Đặt lại mật khẩu', $url)
                ->line("Liên kết có hiệu lực trong {$minutes} phút và chỉ dùng được một lần.")
                ->line('Nếu bạn không yêu cầu, hãy bỏ qua thư này, mật khẩu của bạn không thay đổi.')
                ->salutation('Trân trọng, '.config('app.name'));
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): Password => Password::min(8)
            ->max(72)
            ->letters()
            ->numbers(),
        );
    }
}
