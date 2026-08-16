<?php

namespace App\Providers;

use App\Actions\Jetstream\DeleteUser;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Jetstream\Jetstream;

class JetstreamServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Jetstream::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->configurePermissions();

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Jetstream::deleteUsersUsing(DeleteUser::class);

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Vite::prefetch(concurrency: 3);
    }

    /**
     * Configure the permissions that are available within the application.
     */
    protected function configurePermissions(): void
    {
        Jetstream::defaultApiTokenPermissions(['read']);

        Jetstream::permissions([
            'create',
            'read',
            'update',
            'delete',
        ]);
    }
}
