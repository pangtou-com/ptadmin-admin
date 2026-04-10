<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use PTAdmin\Foundation\Response\AdminResponse;
use PTAdmin\Foundation\Auth\AdminAuth;

class AuthenticateMiddleware
{
    public function handle($request, Closure $next, ...$guards)
    {
        $guards = 0 === \count($guards) ? [config('auth.defaults.guard')] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return $next($request);
            }
        }

        $guard = $guards[0] ?? config('auth.defaults.guard');
        if ('api' === $request->header('X-Method') || $request->expectsJson() || $request->is('api/*')) {
            return AdminResponse::fail(__('ptadmin::background.no_login'), 10001);
        }

        if ($guard === AdminAuth::getGuard()) {
            return redirect()->guest(route('admin_login'));
        }

        return redirect()->guest('/');
    }
}
