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
        $loginAccount = trim((string) ($data['username'] ?? ''));
        $this->checkCode($loginAccount);
        /** @var Admin|null $admin */
        $admin = Admin::query()->where('username', $loginAccount)->first();
        $this->attempt($admin, $loginAccount);
        if (!$admin || !Hash::check($data['password'], $admin->password)) {
            $this->addAttempt();
            $this->log(
                $admin,
                $admin ? AdminLoginLog::STATUS_INVALID_CREDENTIALS : AdminLoginLog::STATUS_USER_NOT_FOUND,
                $admin ? 'password_mismatch' : 'account_not_found',
                $loginAccount
            );

            throw new BackgroundException(__('ptadmin::background.login.fail'));
        }

        // 登录锁定验证
        if (StatusEnum::ENABLE !== $admin->status) {
            $this->addAttempt();
            $this->log($admin, AdminLoginLog::STATUS_DISABLED, 'account_disabled', $loginAccount);

            throw new BackgroundException(__('ptadmin::background.login.limit'));
        }

        $token = Auth::guard(AdminAuth::getGuard())->login($admin);
        $admin->login_ip = request()->getClientIp();
        $admin->login_at = time();
        $admin->save();
        $this->clearAttempt();
        $this->log($admin, AdminLoginLog::STATUS_SUCCESS, 'login_success', $loginAccount);

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
    private function attempt(?Admin $admin = null, string $loginAccount = ''): void
    {
        $num = Cache::get($this->attemptKey(), 0);
        if ($num > 10) {
            $this->log($admin, AdminLoginLog::STATUS_BLOCKED, 'too_many_attempts', $loginAccount);

            throw new BackgroundException(__('ptadmin::background.login.attempt', ['seconds' => 10]));
        }
    }

    /**
     * 记录尝试错误次数.
     */
    private function addAttempt(): void
    {
        $key = $this->attemptKey();
        $num = Cache::get($key, 0);
        ++$num;
        Cache::put($key, $num, now()->addMinutes(10));
    }

    private function clearAttempt(): void
    {
        Cache::forget($this->attemptKey());
    }

    /**
     * 记录登录日志.
     *
     * @param Admin|null $admin
     */
    private function log(?Admin $admin, string $status, string $reason, string $loginAccount = ''): void
    {
        $log = new AdminLoginLog();
        $log->admin_id = $admin ? (int) $admin->id : null;
        $log->login_account = '' !== $loginAccount ? $loginAccount : (string) ($admin->username ?? '');
        $log->login_at = time();
        $log->login_ip = request()->getClientIp();
        $log->status = $status;
        $log->reason = $reason;
        $log->user_agent = $this->normalizeUserAgent(request()->userAgent());
        $log->save();
    }

    /**
     * 校验验证码
     */
    private function checkCode(string $loginAccount = ''): void
    {
    }

    private function attemptKey(): string
    {
        return 'login_'.request()->fingerprint();
    }

    private function normalizeUserAgent(?string $userAgent): ?string
    {
        $userAgent = trim((string) $userAgent);

        return '' === $userAgent ? null : mb_substr($userAgent, 0, 255);
    }
}
