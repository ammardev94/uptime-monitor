<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
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
        // Users, roles, departments, site assignment and global settings
        Gate::define('admin', fn (User $user) => $user->isAdmin());

        Gate::define('view-incidents', fn (User $user) => $user->hasPermission('incidents.view'));
    }
}
