<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use PTAdmin\Admin\Services\Auth\AuthorizationContext;
use PTAdmin\Foundation\Auth\AdminAuth;
use PTAdmin\Contracts\Auth\AuthorizationServiceInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthorizationMiddleware
{
    public function handle($request, Closure $next, $resourceCode, $guard = null)
    {
        $guard = $guard ?: config('auth.defaults.guard');

        if (!Auth::guard($guard)->check()) {
            throw new AccessDeniedHttpException(__('ptadmin::background.access_denied'));
        }

        if ($guard === AdminAuth::getGuard() && AdminAuth::isFounder()) {
            return $next($request);
        }

        $user = Auth::guard($guard)->user();
        $context = AuthorizationContext::fromRequest($request);
        if (app(AuthorizationServiceInterface::class)->allows($user, 'access', (string) $resourceCode, $context)) {
            return $next($request);
        }

        throw new AccessDeniedHttpException(__('ptadmin::background.access_denied'));
    }
}
