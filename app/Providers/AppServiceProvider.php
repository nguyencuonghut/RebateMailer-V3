<?php

namespace App\Providers;

use App\Support\Authorization\RoleName;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        RateLimiter::for('mail-campaign-dispatch', function (): Limit {
            return Limit::perMinute((int) Config::get('mail_campaigns.dispatch.max_per_minute', 50))
                ->by('mail-campaign-dispatch-global');
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $resetUrl = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject(__('Đặt lại mật khẩu'))
                ->view('mail.auth.reset-password', [
                    'appName' => Config::get('app.name'),
                    'resetUrl' => $resetUrl,
                    'expireMinutes' => (int) Config::get(
                        'auth.passwords.'.Config::get('auth.defaults.passwords').'.expire'
                    ),
                    'recipientName' => $notifiable->name,
                    'supportEmail' => Config::get('mail.from.address'),
                ])
                ->text('mail.auth.reset-password-text', [
                    'appName' => Config::get('app.name'),
                    'resetUrl' => $resetUrl,
                    'expireMinutes' => (int) Config::get(
                        'auth.passwords.'.Config::get('auth.defaults.passwords').'.expire'
                    ),
                    'recipientName' => $notifiable->name,
                    'supportEmail' => Config::get('mail.from.address'),
                ]);
        });

        Gate::before(function ($user): bool|null {
            if ($user->hasRole(RoleName::Admin->value)) {
                return true;
            }

            return null;
        });
    }
}
