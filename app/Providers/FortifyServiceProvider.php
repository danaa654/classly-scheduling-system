<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // We are disabling custom responses for now to stop the 403 Forbidden loop.
        // Once login works, you can re-enable these if you have the classes ready.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();

        // Direct Fortify to your redirect logic in web.php
        config(['fortify.home' => '/dashboard']);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        // Using 'pages.auth.login' matches 'resources/views/pages/auth/login.blade.php'
        Fortify::loginView(fn () => view('pages.auth.login'));
        
        Fortify::registerView(fn () => view('pages.auth.register'));
        Fortify::verifyEmailView(fn () => view('pages.auth.verify-email'));
        Fortify::resetPasswordView(fn () => view('pages.auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('pages.auth.forgot-password'));
        Fortify::confirmPasswordView(fn () => view('pages.auth.confirm-password'));
        Fortify::twoFactorChallengeView(fn () => view('pages.auth.two-factor-challenge'));
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());
            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}