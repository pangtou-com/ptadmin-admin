<?php

declare(strict_types=1);

/**
 *  PTAdmin
 *  ============================================================================
 *  版权所有 2022-2026 重庆胖头网络技术有限公司，并保留所有权利。
 *  网站地址: https://www.pangtou.com
 *  ----------------------------------------------------------------------------
 *  尊敬的用户，
 *     感谢您对我们产品的关注与支持。我们希望提醒您，在商业用途中使用我们的产品时，请务必前往官方渠道购买正版授权。
 *  购买正版授权不仅有助于支持我们不断提供更好的产品和服务，更能够确保您在使用过程中不会引起不必要的法律纠纷。
 *  正版授权是保障您合法使用产品的最佳方式，也有助于维护您的权益和公司的声誉。我们一直致力于为客户提供高质量的解决方案，并通过正版授权机制确保产品的可靠性和安全性。
 *  如果您有任何疑问或需要帮助，我们的客户服务团队将随时为您提供支持。感谢您的理解与合作。
 *  诚挚问候，
 *  【重庆胖头网络技术有限公司】
 *  ============================================================================
 *  Author:    Zane
 *  Homepage:  https://www.pangtou.com
 *  Email:     vip@pangtou.com
 */

namespace PTAdmin\Admin\Providers;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use PTAdmin\Admin\Commands\AdminBootstrapAuthCommand;
use PTAdmin\Admin\Http\Middleware\AuthenticateMiddleware;
use PTAdmin\Admin\Http\Middleware\ExceptionResponseMiddleware;
use PTAdmin\Admin\Commands\AdminInitCommand;
use PTAdmin\Admin\Http\Middleware\AuthorizationMiddleware;
use PTAdmin\Admin\Http\Middleware\OperationRecordMiddleware;
use PTAdmin\Admin\Services\Auth\AuthorizationContext;
use PTAdmin\Admin\Services\Auth\AuthorizationService;
use PTAdmin\Admin\Services\Auth\CapabilityService;
use PTAdmin\Admin\Services\Auth\AdminGrantService;
use PTAdmin\Admin\Services\Auth\AdminOrganizationService;
use PTAdmin\Admin\Services\Auth\AdminResourceService;
use PTAdmin\Admin\Services\Auth\AdminRoleService;
use PTAdmin\Admin\Services\Auth\AdminTenantService;
use PTAdmin\Admin\Services\Auth\WorkflowService;
use PTAdmin\Foundation\Auth\AddonGuard;
use PTAdmin\Foundation\Auth\AdminAuth;
use PTAdmin\Contracts\Auth\AuthorizationServiceInterface;
use PTAdmin\Contracts\Auth\CapabilityServiceInterface;
use PTAdmin\Contracts\Auth\AdminGrantServiceInterface;
use PTAdmin\Contracts\Auth\AdminOrganizationServiceInterface;
use PTAdmin\Contracts\Auth\AdminResourceServiceInterface;
use PTAdmin\Contracts\Auth\AdminRoleServiceInterface;
use PTAdmin\Contracts\Auth\AdminTenantServiceInterface;
use PTAdmin\Contracts\Auth\WorkflowServiceInterface;
use PTAdmin\Easy\Providers\EasyServiceProviders;

class PTAdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../config/ptadmin-auth.php', 'ptadmin-auth');
        $this->app->register(EasyServiceProviders::class);

        $this->app->singleton(AuthorizationServiceInterface::class, AuthorizationService::class);
        $this->app->singleton(CapabilityServiceInterface::class, CapabilityService::class);
        $this->app->singleton(AdminResourceServiceInterface::class, AdminResourceService::class);
        $this->app->singleton(AdminGrantServiceInterface::class, AdminGrantService::class);
        $this->app->singleton(AdminRoleServiceInterface::class, AdminRoleService::class);
        $this->app->singleton(AdminTenantServiceInterface::class, AdminTenantService::class);
        $this->app->singleton(AdminOrganizationServiceInterface::class, AdminOrganizationService::class);
        $this->app->singleton(WorkflowServiceInterface::class, WorkflowService::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AdminBootstrapAuthCommand::class,
                AdminInitCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../../../config/ptadmin-auth.php' => config_path('ptadmin-auth.php'),
            ], 'ptadmin-config');

            $this->publishes([
                __DIR__.'/../../../database/Migrations/2026_04_09_120000_create_admin_authorization_tables.php' => database_path('migrations/2026_04_09_120000_create_admin_authorization_tables.php'),
                __DIR__.'/../../../database/Migrations/2026_04_09_130000_create_admin_authorization_extension_tables.php' => database_path('migrations/2026_04_09_130000_create_admin_authorization_extension_tables.php'),
                __DIR__.'/../../../database/Migrations/2026_04_09_140000_seed_admin_default_resources.php' => database_path('migrations/2026_04_09_140000_seed_admin_default_resources.php'),
            ], 'ptadmin-migrations');

            $this->publishes([
                __DIR__.'/../../../lang' => resource_path('lang/vendor/ptadmin'),
            ], 'ptadmin-lang');
        }

        $this->loadMigrationsFrom(__DIR__.'/../../../database/Migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../../lang', 'ptadmin');
        $this->registerRouteMiddleware();
        $this->extendGuard();
        $this->registerAuthorizationGate();
        $this->mapSystemRoutes();
    }

    protected function extendGuard(): void
    {
        Auth::resolved(function ($auth): void {
            $auth->extend('ptadmin', function ($app, $name, array $config) use ($auth) {
                return tap($this->createGuard($auth, $name, $config), function ($guard): void {
                    app()->refresh('request', $guard, 'setRequest');
                });
            });
        });
    }

    protected function createGuard($auth, $guard_name, $config): AddonGuard
    {
        return new AddonGuard($auth, $guard_name, request(), $auth->createUserProvider($config['provider'] ?? null));
    }

    /**
     * 扩展创始人权限.当角色为创始人时默认情况下有所有的权限.
     */
    private function registerAuthorizationGate(): void
    {
        app(Gate::class)->before(function (Authorizable $user = null, string $ability = '') {
            $guardName = func_get_arg(2)[0] ?? null;
            if (null === $guardName || !Auth::guard($guardName)->check()) {
                return null;
            }

            if ($guardName === AdminAuth::getGuard() && AdminAuth::isFounder()) {
                return true;
            }

            $context = AuthorizationContext::fromRequest(request());

            return app(AuthorizationServiceInterface::class)->allows($user, 'access', $ability, $context);
        });
    }

    private function mapSystemRoutes(): void
    {
        Route::middleware(['api', 'ptadmin.response', 'ptadmin.operation.record'])->group(__DIR__.'/../../../routes/admin.php');
    }

    private function registerRouteMiddleware(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('ptadmin.auth', AuthenticateMiddleware::class);
        $router->aliasMiddleware('ptadmin.response', ExceptionResponseMiddleware::class);
        $router->aliasMiddleware('ptadmin.resource', AuthorizationMiddleware::class);
        $router->aliasMiddleware('ptadmin.operation.record', OperationRecordMiddleware::class);
    }
}
