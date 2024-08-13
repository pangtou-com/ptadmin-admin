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

namespace PTAdmin\Admin\Service;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use PTAdmin\Admin\Enum\StatusEnum;
use PTAdmin\Admin\Exceptions\BackgroundException;
use PTAdmin\Admin\Models\System;
use PTAdmin\Admin\Models\SystemLog;
use PTAdmin\Admin\Utils\SystemAuth;

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
        /** @var System $system */
        $system = System::query()->where('username', $data['username'])->first();
        $this->attempt();
        if (!$system || !Hash::check($data['password'], $system->password)) {
            $this->addAttempt();
            $this->log($system);

            throw new BackgroundException(__('background.login.fail'));
        }

        // 登录锁定验证
        if (StatusEnum::ENABLE !== $system->status) {
            $this->addAttempt();
            $this->log($system);

            throw new BackgroundException(__('background.login.limit'));
        }

        $token = Auth::guard(SystemAuth::getGuard())->login($system);
        $system->login_ip = request()->getClientIp();
        $system->login_at = time();
        $system->save();
        $this->log($system, StatusEnum::ENABLE);

        return [
            'nickname' => $system->nickname,
            'username' => $system->username,
            'mobile' => $system->mobile,
            'avatar' => $system->avatar,
            'email' => $system->email,
            'login_at' => $system->login_at,
            'token' => $token,
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
            throw new BackgroundException(__('background.login.attempt', ['seconds' => 10]));
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
     * @param $system
     * @param int $status
     */
    private function log($system, int $status = StatusEnum::DISABLE): void
    {
        if (!$system) {
            return;
        }
        $log = new SystemLog();
        $log->system_id = $system->id;
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
