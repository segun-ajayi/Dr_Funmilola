<?php

namespace App\Providers;

use App\Contracts\VideoProviderInterface;
use App\Services\Video\UnconfiguredVideoProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(VideoProviderInterface::class,UnconfiguredVideoProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(fn (object $notifiable, string $token) => url('/reset-password').'?token='.urlencode($token).'&email='.urlencode($notifiable->getEmailForPasswordReset()));
    }
}
