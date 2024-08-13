<?php

declare(strict_types=1);

/**
 *  PTAdmin
 *  ============================================================================
 *  版权所有 2022-2024 重庆胖头网络技术有限公司，并保留所有权利。
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

namespace PTAdmin\Admin\Utils;

use Illuminate\Support\Facades\Auth;
use PTAdmin\Admin\Exceptions\BackgroundException;
use PTAdmin\Admin\Models\System;
use PTAdmin\Admin\Service\PermissionService;

class SystemAuth
{
    /**
     * 是否为创始人账户.
     *
     * @return bool
     */
    public static function IsFounder(): bool
    {
        /** @var System $user */
        $user = self::user();

        return (bool) $user->is_founder;
    }

    /**
     * 获取当前登录用户信息.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public static function user(): \Illuminate\Contracts\Auth\Authenticatable
    {
        if (!self::check()) {
            throw new BackgroundException(__('background.no_login'));
        }

        return Auth::guard(self::getGuard())->user();
    }

    /**
     * 校验是否登录.
     *
     * @return bool
     */
    public static function check(): bool
    {
        return Auth::guard(self::getGuard())->check();
    }

    /**
     * 通过缓存的方式获取用户的后台管理权限.
     *
     * @param $user
     *
     * @return string
     */
    public static function adminPermNav($user = null): string
    {
        if (!$user) {
            /** @var System $user */
            $user = self::user();
        }

        $permService = app(PermissionService::class);
        $perm = $permService->myPermission($user);

        return $permService->adminPermNav($perm);
    }

    /**
     * 根据请求方法获取分组.
     *
     * @return \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed|string
     */
    public static function getGuard()
    {
        return config('auth.app_guard_name', 'admin');
    }
}
