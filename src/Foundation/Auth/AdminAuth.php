<?php

declare(strict_types=1);

namespace PTAdmin\Foundation\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use PTAdmin\Admin\Models\System;
use PTAdmin\Admin\Services\AdminResourceService;
use PTAdmin\Foundation\Exceptions\BackgroundException;

class AdminAuth
{
    public static function isFounder(): bool
    {
        /** @var System $user */
        $user = self::user();

        return (bool) $user->is_founder;
    }

    public static function user(): Authenticatable
    {
        if (!self::check()) {
            throw new BackgroundException(__('ptadmin::background.no_login'));
        }

        return Auth::guard(self::getGuard())->user();
    }

    public static function check(): bool
    {
        return Auth::guard(self::getGuard())->check();
    }

    public static function adminResourceNav($user = null): string
    {
        if (!$user) {
            /** @var System $user */
            $user = self::user();
        }

        $resourceService = app(AdminResourceService::class);
        $resources = $resourceService->myResources($user);

        return $resourceService->adminResourceNav($resources);
    }

    public static function getGuard()
    {
        return config('ptadmin-auth.guard', config('auth.app_guard_name', 'admin'));
    }
}
