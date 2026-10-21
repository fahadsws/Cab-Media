<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Super Admin bypasses every permission check.
        Gate::before(fn ($user) => $user->hasRole('Super Admin') ? true : null);

        // Bulk-action config for admin DataTables comes from the current controller (see HandlesBulk).
        View::composer('admin.partials.dt', function ($view) {
            $c = request()->route()?->getController();
            if ($c && in_array(\App\Http\Controllers\Admin\Concerns\HandlesBulk::class, class_uses_recursive($c), true)) {
                $view->with($c->bulkViewData(request()));
            }
        });
    }
}
