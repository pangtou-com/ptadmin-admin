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

namespace PTAdmin\Admin\Service\Auth;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use PTAdmin\Admin\Exceptions\BackgroundException;
use PTAdmin\Admin\Models\Traits\HasApiTokens;
use PTAdmin\Admin\Models\UserToken;

class AddonGuard implements Guard
{
    use GuardHelpers, Macroable {
        __call as macroCall;
    }
    /** @var \Illuminate\Contracts\Auth\Factory 验证实现工厂 */
    protected $auth;

    /** @var Request */
    protected $request;

    /** @var string 服务提供者 */
    protected $provider;

    /** @var string 警卫 */
    protected $guard_name;

    public function __construct(AuthFactory $auth, $guard_name, $request, $provider = null)
    {
        $this->auth = $auth;
        $this->request = $request;
        $this->provider = $provider;
        $this->guard_name = $guard_name;
    }

    /**
     * 获取授权用户信息.
     *
     * @return null|\Illuminate\Contracts\Auth\Authenticatable
     */
    public function user(): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        if (null !== $this->user) {
            return $this->user;
        }
        $accessToken = $this->getAccessToken();
        if (null === $accessToken) {
            return null;
        }
        // 更新最近一次使用时间
        $accessToken->forceFill(['last_used_at' => now()->getTimestamp()])->save();

        return $accessToken->target;
    }

    /**
     * 登录.
     *
     * @param $user
     * @param mixed $remember 是否记住
     *
     * @return string
     */
    public function login($user, $remember = false): string
    {
        if ($this->supportsTokens($user)) {
            return $user->createToken($this->guard_name)->plainTextToken;
        }

        throw new BackgroundException('用户模型必须使用【HasApiTokens trait】.');
    }

    /**
     * 退出登录.
     */
    public function logout(): void
    {
        $accessToken = $this->getAccessToken();
        if (null !== $accessToken) {
            $accessToken->delete();
        }
    }

    /**
     * 从请求中获取令牌信息.
     *
     * @return null|string
     */
    public function getTokenFromRequest(): ?string
    {
        $token = $this->request ? $this->request->bearerToken() : null;

        return $this->isValidBearerToken($token) ? $token : null;
    }

    public function validate(array $credentials = []): bool
    {
        return false;
    }

    /**
     * 通过请求获取访问令牌.
     *
     * @return null|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|object|UserToken|void
     */
    public function getAccessToken()
    {
        $token = $this->getTokenFromRequest();
        if (null === $token) {
            return null;
        }
        $accessToken = \PTAdmin\Admin\Models\UserToken::findToken($token);

        if (!$this->isValidAccessToken($accessToken) || !$this->supportsTokens($accessToken->target)) {
            return null;
        }
        $accessToken->target->withAccessToken($accessToken);

        return $accessToken;
    }

    /**
     * 查看模型是否支持Tokens API.
     *
     * @param $token_able
     *
     * @return bool
     */
    protected function supportsTokens($token_able = null): bool
    {
        return $token_able && \in_array(HasApiTokens::class, class_uses_recursive(
            \get_class($token_able)
        ), true);
    }

    /**
     * 验证token是否有效.
     *
     * @param null|string $token
     *
     * @return bool
     */
    protected function isValidBearerToken(string $token = null): bool
    {
        if (null !== $token && Str::contains($token, '|')) {
            $model = new UserToken();

            if ('int' === $model->getKeyType()) {
                [$id, $token] = explode('|', $token, 2);

                return ctype_digit($id) && !blank($token);
            }
        }

        return !blank($token);
    }

    /**
     * 验证密钥是否有效.
     *
     * @param $accessToken
     *
     * @return bool
     */
    protected function isValidAccessToken($accessToken): bool
    {
        if (!$accessToken) {
            return false;
        }
        if (!$this->hasValidProvider($accessToken->target)) {
            return false;
        }
        $created_at = (int) $accessToken->getRawOriginal('created_at');

        // 自定义过期时间
        $expires_at = (int) $accessToken->getRawOriginal('expires_at');
        if (0 !== $expires_at) {
            // 如果为一个有效的时间戳则判断是否大于当前时间
            if (Carbon::make($expires_at)->isValid()) {
                return $expires_at > now()->getTimestamp();
            }

            return $created_at < now()->subSeconds($expires_at)->getTimestamp();
        }

        // 默认过期时间24小时
        $expiration = config("auth.guards.{$this->guard_name}.expires_at", 24 * 60 * 60);
        if (blank($expiration) || $created_at < now()->subSeconds($expiration)->getTimestamp()) {
            return false;
        }

        return true;
    }

    /**
     * 验证模型是否与需求模型一致.
     *
     * @param $token_able
     *
     * @return bool
     */
    protected function hasValidProvider($token_able): bool
    {
        if (null === $this->provider) {
            return true;
        }
        $provider = config("auth.guards.{$this->guard_name}.provider");
        $model = config("auth.providers.{$provider}.model");

        return $token_able instanceof $model;
    }
}
