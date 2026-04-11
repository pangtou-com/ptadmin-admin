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

namespace PTAdmin\Admin\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use PTAdmin\Admin\Models\Admin;
use PTAdmin\Admin\Models\AdminLoginLog;
use PTAdmin\Foundation\Auth\AdminAuth;
use PTAdmin\Foundation\Exceptions\BackgroundException;
use PTAdmin\Support\Enums\StatusEnum;

class LoginService
{
    /**
     * 登录处理.
     *
     * @param array $data
     *
     * @return array
     */
    public function login(array $data): array
    {
        $this->checkCode();
        /** @var Admin|null $admin */
        $admin = Admin::query()->where('username', $data['username'])->first();
        $this->attempt();
        if (!$admin || !Hash::check($data['password'], $admin->password)) {
            $this->addAttempt();
            $this->log($admin);

            throw new BackgroundException(__('ptadmin::background.login.fail'));
        }

        // 登录锁定验证
        if (StatusEnum::ENABLE !== $admin->status) {
            $this->addAttempt();
            $this->log($admin);

            throw new BackgroundException(__('ptadmin::background.login.limit'));
        }

        $token = Auth::guard(AdminAuth::getGuard())->login($admin);
        $admin->login_ip = request()->getClientIp();
        $admin->login_at = time();
        $admin->save();
        $this->log($admin, StatusEnum::ENABLE);

        return [
            'token' => $token,
            'user' => [
                'id' => $admin->id,
                'nickname' => $admin->nickname,
                'username' => $admin->username,
                'mobile' => $admin->mobile,
                'avatar' => $admin->avatar,
                'email' => $admin->email,
                'login_at' => $admin->login_at,
                'login_ip' => $admin->login_ip,
            ],
        ];
    }

    /**
     * 校验登录尝试次数.
     */
    private function attempt(): void
    {
        $key = request()->fingerprint();
        $num = Cache::get('login_'.$key);
        if ($num > 10) {
            throw new BackgroundException(__('ptadmin::background.login.attempt', ['seconds' => 10]));
        }
    }

    /**
     * 记录尝试错误次数.
     */
    private function addAttempt(): void
    {
        $key = request()->fingerprint();
        $num = Cache::get($key, 0);
        ++$num;
        Cache::put($key, $num, now()->addMinutes(10));
    }

    /**
     * 记录登录日志.
     *
     * @param Admin|null $admin
     * @param int $status
     */
    private function log(?Admin $admin, int $status = StatusEnum::DISABLE): void
    {
        if (!$admin) {
            return;
        }
        $log = new AdminLoginLog();
        $log->admin_id = $admin->id;
        $log->login_at = time();
        $log->login_ip = (int) ip2long(request()->getClientIp());
        $log->status = $status;
        $log->save();
    }

    /**
     * 校验验证码
     */
    private function checkCode(): void
    {
    }
}
