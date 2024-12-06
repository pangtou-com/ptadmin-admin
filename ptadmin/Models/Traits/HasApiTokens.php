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

namespace PTAdmin\Admin\Models\Traits;

use Illuminate\Support\Str;
use PTAdmin\Admin\Models\UserToken;
use PTAdmin\Admin\Service\Auth\NewAccessToken;

trait HasApiTokens
{
    /**
     * @var UserToken 当前请求的访问令牌
     */
    protected $accessToken;

    public function tokens(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(UserToken::class, 'target');
    }

    /**
     * 生成token字符串.
     *
     * @return string
     */
    public function generateToken(): string
    {
        $token = Str::random(40);

        return sprintf('%s%s%s', config('auth.ptadmin_auth_prefix', 'ptadmin'), $token, hash('crc32b', $token));
    }

    /**
     * 创建新的访问令牌.
     *
     * @param $guard_name
     * @param null $expiresAt
     *
     * @return NewAccessToken
     */
    public function createToken($guard_name = null, $expiresAt = null): NewAccessToken
    {
        $plainTextToken = $this->generateToken();
        $guard_name = null === $guard_name ? config('auth.defaults.guard') : $guard_name;

        $ip = (int) ip2long(request()->getClientIp());

        /** @var UserToken $token */
        $token = $this->tokens()->create([
            'guard_name' => $guard_name,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => (int) $expiresAt,
            'ip' => $ip,
        ]);

        return new NewAccessToken($token, $token->getKey().'|'.$plainTextToken);
    }

    /**
     * 获取当前访问令牌.
     */
    public function getCurrentAccessToken(): ?UserToken
    {
        return $this->accessToken;
    }

    /**
     * 设置当前访问令牌.
     *
     * @param $accessToken
     *
     * @return \App\Models\User|HasApiTokens
     */
    public function withAccessToken($accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }
}
