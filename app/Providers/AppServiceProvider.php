<?php

namespace App\Providers;

use App\Services\ActiveBranchService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(ActiveBranchService::class, fn ($app) => new ActiveBranchService());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer('*', function ($view): void {
            if (! auth()->check()) {
                return;
            }

            $activeBranchService = app(ActiveBranchService::class);

            $view->with('currentBranch', $activeBranchService->ensureActiveBranch(auth()->user()));
        });
    }
}
