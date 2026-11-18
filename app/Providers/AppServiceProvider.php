<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
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
        Paginator::useBootstrapFive();

        View::share('branding', config('branding'));

        // =========================================================================
        // AUTHORIZATION GATES
        // =========================================================================
        Gate::define('admin-management', function (User $user) {
            return $user->isAdminManagement();
        });

        Gate::define('admin-inventory', function (User $user) {
            return $user->isAdminInventory();
        });

        Gate::define('manage-inventory', function (User $user) {
            return $user->canManageInventory();
        });

        Gate::define('manage-users', function (User $user) {
            return $user->canManageUsers();
        });

        Gate::define('manage-module', function (User $user, string $module = '') {
            return $user->canManageModule($module);
        });

        Gate::define('export-pdf', function (User $user, ?string $module = null) {
            return $user->canExportPdf($module);
        });

        // =========================================================================
        // CUSTOM BLADE DIRECTIVES
        // =========================================================================
        Blade::if('adminManagement', function () {
            return auth()->check() && auth()->user()->isAdminManagement();
        });

        Blade::if('adminInventory', function () {
            return auth()->check() && auth()->user()->isAdminInventory();
        });

        Blade::if('canManageInventory', function () {
            return auth()->check() && auth()->user()->canManageInventory();
        });

        Blade::if('canManageModule', function (string $module = '') {
            return auth()->check() && auth()->user()->canManageModule($module);
        });

        Blade::if('canExportPdf', function (?string $module = null) {
            $resolvedModule = $module ?: (request()->routeIs('events.*') ? 'events' : (request()->routeIs('daily-work.*') ? 'daily_work' : (request()->routeIs('kpi-monitoring.*', 'hsse-plan.*') ? 'kpi-monitoring' : null)));
            return auth()->check() && auth()->user()->canExportPdf($resolvedModule);
        });

        Blade::if('canExport', function (?string $module = null) {
            $resolvedModule = $module ?: (request()->routeIs('events.*') ? 'events' : (request()->routeIs('daily-work.*') ? 'daily_work' : (request()->routeIs('kpi-monitoring.*', 'hsse-plan.*') ? 'kpi-monitoring' : null)));
            return auth()->check() && auth()->user()->canExportPdf($resolvedModule);
        });
    }
}
