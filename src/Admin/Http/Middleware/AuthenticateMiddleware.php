<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PTAdmin\Foundation\Exceptions\ServiceException;
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
        if ($this->shouldReturnJsonResponse($request)) {
            return AdminResponse::fail(__('ptadmin::background.no_login'), 419);
        }
        
        if ($guard === AdminAuth::getGuard()) {
            return redirect()->guest(route('admin_login_notice', [
                'redirect' => '/'.ltrim($request->getRequestUri(), '/'),
            ]));
        }

        return redirect()->guest('/');
    }

    protected function shouldReturnJsonResponse(Request $request): bool
    {
        return 'api' === $request->header('X-Method')
            || $request->expectsJson()
            || $request->wantsJson()
            || $request->ajax()
            || $request->isXmlHttpRequest()
            || $request->is('api/*');
    }
}
