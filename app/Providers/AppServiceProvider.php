<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
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
        // Sem rota "dashboard": um admin já logado que abrir /admin/login vai para o painel, não para o site público.
        RedirectIfAuthenticated::redirectUsing(fn () => route('admin.dashboard'));
    }
}
