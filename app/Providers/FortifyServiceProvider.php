<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Fortify::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Fortify::createUsersUsing(CreateNewUser::class);
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        RateLimiter::for('login', function (Request $request) {
            // Luong: Gan ket qua xu ly vao bien $throttleKey.
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            // Luong: Tra ve ket qua cuoi cung cua ham.
            return Limit::perMinute(5)->by($throttleKey);
        });

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        RateLimiter::for('two-factor', function (Request $request) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
