<?php

namespace App\Providers;

use App\Contracts\FileScannerInterface;
use App\Contracts\VideoProviderInterface;
use App\Services\Security\BasicFileScanner;
use App\Services\Security\UnconfiguredFileScanner;
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
        $this->app->bind(VideoProviderInterface::class, UnconfiguredVideoProvider::class);
        $this->app->bind(FileScannerInterface::class, fn () => match (config('upload-security.scanner')) {
            'basic' => new BasicFileScanner,
            default => new UnconfiguredFileScanner,
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(fn (object $notifiable, string $token) => url('/reset-password').'?token='.urlencode($token).'&email='.urlencode($notifiable->getEmailForPasswordReset()));
    }
}
