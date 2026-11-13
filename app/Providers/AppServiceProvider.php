<?php

namespace App\Providers;

use App\Models\Store;
use App\Models\User;
use App\Services\AuthorizationService;
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
        // 1. Dynamic permission gates from AuthorizationService
        foreach (AuthorizationService::getAllPermissions() as $permission) {
            Gate::define($permission, function (User $user) use ($permission) {
                return $user->hasPermission($permission);
            });
        }

        // 2. Named role gates
        Gate::define('admin', fn (User $user) => $user->isAdmin());
        Gate::define('manager', fn (User $user) => $user->isManager());
        Gate::define('asst-treasury-manager', fn (User $user) => $user->hasRole(AuthorizationService::ROLE_ASST_TREASURY_MANAGER));
        Gate::define('asst-cashier-manager', fn (User $user) => $user->hasRole(AuthorizationService::ROLE_ASST_CASHIER_MANAGER));
        Gate::define('treasury-supervisor', fn (User $user) => $user->hasRole(AuthorizationService::ROLE_AREA_TREASURY_SUPERVISOR));
        Gate::define('cashier-supervisor', fn (User $user) => $user->hasRole(AuthorizationService::ROLE_AREA_CASHIER_SUPERVISOR));

        // 3. Domain-specific data scoping gates
        Gate::define('access-audit-type', function (User $user, string $type) {
            return $user->canAccessAuditType($type);
        });

        Gate::define('access-store', function (User $user, int|Store $store, ?string $auditType = null) {
            return $user->canAccessStore($store, $auditType);
        });

        Gate::define('execute-checklist', function (User $user, int|Store $store, string $type) {
            return $user->isSupervisor()
                && $user->canAccessAuditType($type)
                && $user->canAccessStore($store, $type);
        });

        Gate::define('log-observation', function (User $user, int|Store $store, string $type) {
            return $user->isSupervisor()
                && $user->canAccessAuditType($type)
                && $user->canAccessStore($store, $type);
        });
    }
}
