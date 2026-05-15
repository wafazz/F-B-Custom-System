<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // super_admin bypasses every gate / permission check so it can never
        // be locked out of newly added resources.
        Gate::before(fn ($user) => $user?->hasRole('super_admin') ? true : null);
    }
}
