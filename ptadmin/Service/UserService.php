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

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PTAdmin\Admin\Enum\StatusEnum;
use PTAdmin\Admin\Exceptions\BackgroundException;
use PTAdmin\Admin\Models\User;
use PTAdmin\Admin\Models\UserBindPlatform;
use PTAdmin\Admin\Models\UserMoney;
use SebastianBergmann\Template\RuntimeException;

class UserService
{
    protected $openService;

    public function __construct(UserBindPlatformService $openService)
    {
        $this->openService = $openService;
    }

    /**
     * 新增.
     *
     * @param $data
     *
     * @return User
     */
    public function store($data): User
    {
        $dao = new User();
        $dao->fill($data);
        $dao->salt = Str::random(4);
        $dao->password = Hash::make($data['password'].$dao->salt);
        $dao->join_at = time();
        $dao->join_ip = (int) ip2long(request()->getClientIp());
        $dao->save();

        return $dao;
    }

    public function page($search = []): array
    {
        $allow = [
            'username ' => ['op' => 'like'],
            'nickname' => ['op' => 'like'],
            'mobile' => ['op' => 'like'],
            'email' => ['op' => 'like'],
            'money' => ['filter' => 'toFloat'],
            'score' => ['filter' => 'toInt'],
            'status' => ['op' => 'IN', 'field' => 'status'],
        ];
        $model = User::search($allow, $search);

        return $model->orderBy('id', 'desc')->paginate()->toArray();
    }

    /**
     * 后端编辑. 前端修改密码逻辑需要单独处理.
     *
     * @param $id
     * @param $data
     */
    public function edit($id, $data): void
    {
        $user = $this->byId($id);
        if (isset($data['password']) && $data['password']) {
            $user->salt = Str::random(4);
            $data['password'] = Hash::make($data['password'].$user->salt);
        } else {
            unset($data['password']);
        }
        $user->update($data);
    }

    public function byId($id)
    {
        return User::query()->findOrFail($id);
    }

    /**
     * 第三方登录授权.
     *
     * @param $tag
     *
     * @return array
     */
    public function otherOauth($tag): array
    {
        $config = SettingService::byGroupingName('oauth');
        if (!isset($config[$tag])) {
            throw new BackgroundException('请先配置第三方登录信息');
        }
//        $oauth = Oauth::make($tag, $config[$tag]);
//        if (Auth::guard('web')->check()) {
//            // TODO 待思考 ？？ 绑定
//            // $oauth->setUserId(Auth::guard('web')->id());
//        }

//         $url = $oauth->buildRequestCodeURL();

        return [
            'type' => 'url',
            'url' => '',
        ];
    }

    /**
     * 第三方登录回调处理.
     *
     * @param $data
     * @param mixed $tag
     *
     * @return array
     */
    public function otherOauthCallback($data, $tag): array
    {
        $config = SettingService::byGroupingName('oauth');
        if (!isset($config[$tag])) {
            throw new BackgroundException('请先配置第三方登录信息');
        }
        // $oauth = Oauth::make($tag, $config[$tag])->setCallbackParams($data);
        $oauth = app('oauth');
        $openid = $oauth->getOpenId();
        /** @var UserBindPlatform $open */
        $open = $this->openService->byOpenId($openid, $tag);

        $state = explode('_', $data['state']);
        $state_user_id = (int) $state[0];

        if ($state_user_id > 0) {
            // TODO 绑定用户逻辑还需要处理：1、用户已经绑定过，切与当前账户不一致时 2、用户未绑定过，绑定用户
            $openUserinfo = $oauth->getUserinfo();
            if (!$open) {
                DB::beginTransaction();

                try {
                    $this->openService->store([
                        'user_id' => $state_user_id,
                        'nickname' => $openUserinfo['nickname'],
                        'avatar' => $openUserinfo['avatar'],
                        'source' => $tag,
                        'open_id' => $openid,
                        'union_id' => 0,
                    ]);
                    DB::commit();
                } catch (\Exception $exception) {
                    DB::rollBack();

                    throw new BackgroundException($exception->getMessage());
                }
            } else {
                $open->user_id = $state_user_id;
                $open->nickname = $openUserinfo['nickname'];
                $open->avatar = $openUserinfo['avatar'];
                $open->save();
            }

            return [];
        }

        //注册登录
        if (!$open) {
            $openUserinfo = $oauth->getUserinfo();
            DB::beginTransaction();

            try {
                $user = $this->register([
                    'username' => $openUserinfo['nickname'],
                    'nickname' => $openUserinfo['nickname'],
                    'password' => Str::random(),
                    'avatar' => $openUserinfo['avatar'],
                ]);
                $this->openService->store([
                    'user_id' => $user->id,
                    'nickname' => $openUserinfo['nickname'],
                    'avatar' => $openUserinfo['avatar'],
                    'source' => $tag,
                    'open_id' => $openid,
                    'union_id' => 0,
                ]);
                DB::commit();
            } catch (\Exception $exception) {
                DB::rollBack();

                throw new BackgroundException($exception->getMessage());
            }
        } else {
            $user = $this->byId($open->user_id);
        }

        //TODO 如果为API接口登录需要返回的是token信息
        Auth::guard('web')->login($user, $data['remember'] ?? 0);
        // 记录登录信息
        $this->recordLogin($user->id);

        return [];
    }

    /**
     * 第三方授权解绑.
     *
     * @param $user_id
     * @param $source
     */
    public function unbindOauth($user_id, $source): void
    {
        $config = SettingService::byGroupingName('oauth');
        if (!isset($config[$source])) {
            throw new BackgroundException('第三方登录配置信息错误');
        }
        /** @var UserBindPlatform $dao */
        $dao = UserBindPlatform::query()
            ->where('user_id', $user_id)
            ->where('source', $source)->firstOrFail();

        $dao->user_id = 0;
        $dao->save();
    }

    /**
     * 注册.
     *
     * @param $data
     *
     * @return User
     */
    public function register($data): User
    {
        // 注册逻辑
        $user = $this->store($data);
        if (blank($user->nickname)) {
            $user->nickname = get_mix_user_id($user->id);
        }
        if (blank($user->avatar)) {
            $user->avatar = user_avatar($user->id);
        }

        $user->save();

        return $user;
    }

    /**
     * 重置密码.
     *
     * @param $data
     */
    public function reset($data): void
    {
        //1 手机号重置密码 2 邮箱重置密码
        if (1 === (int) $data['type']) {
            $user = User::query()->where('mobile', $data['mobile'])->first();
            if (!$user) {
                throw new BackgroundException('手机号未注册');
            }
        } else {
            $user = User::query()->where('email', $data['email'])->first();
            if (!$user) {
                throw new RuntimeException('邮箱未注册');
            }
        }
        $user->salt = Str::random(4);
        $user->password = Hash::make($data['password'].$user->salt);
        $user->save();
    }

    /**
     * 用户登录.
     *
     * @param array  $data  用户信息
     * @param string $guard 守卫分组
     *
     * @return array
     */
    public function login(array $data, string $guard = 'web'): array
    {
        if (Auth::guard($guard)->check()) {
            throw new BackgroundException('用户已登录');
        }
        // 当存在type时认为是手机或者邮箱验证码登录
        $user = (isset($data['type']) && 1 === (int) $data['type']) ? $this->attemptCode($data) : $this->attempt($data);

        // 登录锁定验证
        if (StatusEnum::ENABLE !== $user->status) {
            throw new BackgroundException(__('background.login.limit'));
        }

        $token = Auth::guard($guard)->login($user);

        // 记录登录信息
        $this->recordLogin($user->id);

        return [
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'token' => $token,
        ];
    }

    public function logout($guard = 'web'): void
    {
        Auth::guard($guard)->logout();
    }

    /**
     * 获取授权数.
     *
     * @param $user_id
     * @param $source
     *
     * @return int
     */
    public function getAuthNum($user_id, $source): int
    {
        return UserBindPlatform::query()->where('user_id', $user_id)->where('source', $source)->count();
    }

    /**
     * 获取消费金额.
     *
     * @param $user_id
     *
     * @return mixed
     */
    public function getConsumeAmount($user_id)
    {
        $model = UserMoney::query();
        $model->where('user_id', $user_id);
        $model->where('type', 0);

        return $model->sum('money');
    }

    /**
     * 修改个人信息.
     *
     * @param int   $user_id
     * @param array $data
     */
    public function updateProfile(int $user_id, array $data): void
    {
        $user = $this->byId($user_id);
        $user->avatar = $data['avatar'];
        $user->nickname = $data['nickname'];
        if (isset($data['password']) && $data['password']) {
            $user->salt = Str::random(4);
            $user->password = Hash::make($data['password'].$user->salt);
        }
        $user->bio = $data['bio'];
        $user->save();
    }

    /**
     * 绑定/解绑手机号.
     *
     * @param int    $user_id
     * @param string $mobile
     */
    public function updateMobile(int $user_id, string $mobile): void
    {
        $user = $this->byId($user_id);
        $user->mobile = $mobile;
        $user->save();
    }

    /**
     * 绑定/解绑邮箱.
     *
     * @param int    $user_id
     * @param string $email
     */
    public function updateEmail(int $user_id, string $email): void
    {
        $user = $this->byId($user_id);
        $user->email = $email;
        $user->save();
    }

    /**
     * 当为API接口登录时返回token信息.
     *
     * @param $user
     * @param $guard
     *
     * @return null|string
     */
    protected function getToken($user, $guard): ?string
    {
        $driver = config("auth.guards.{$guard}.driver", 'session');
        if ('ptadmin' === $driver) {
            return $user->createToken('token', [$guard])->plainTextToken;
        }

        return null;
    }

    /**
     * 尝试登录.
     *
     * @param $data
     *
     * @return User
     */
    private function attempt($data): User
    {
        /** @var User $user */
        $user = User::query()->where('username', $data['username'])->first();
        if (!$user || !Hash::check($data['password'].$user->salt, $user->password)) {
            throw new BackgroundException(__('background.login.fail'));
        }

        return $user;
    }

    /**
     * 尝试使用手机或者邮箱验证码登录.
     *
     * @param $data
     *
     * @return User
     */
    private function attemptCode($data): User
    {
        if (!isset($data['code'])) {
            throw new BackgroundException('请输入验证码');
        }
        $field = 'mobile';
        $type = UserVerifyService::TYPE_SMS;
        if (is_email($data['username'])) {
            $type = UserVerifyService::TYPE_EMAIL;
            $field = 'email';
        }

        UserVerifyService::verifyCode($data['username'], $data['code'], UserVerifyService::SCENE_LOGIN, $type);

        /** @var User $user */
        $user = User::query()->where($field, $data['username'])->first();
        if (!$user) {
            // 开启自动注册后自动注册，默认未开启状态
            if (true === setting('is_register', true)) {
                $data['password'] = Str::random();
                $data['avatar'] = user_avatar();
                $data['status'] = StatusEnum::ENABLE;
                $data['join_at'] = time();
                $data['join_ip'] = (int) ip2long(request()->getClientIp());
                $data[$field] = $data['username'];

                return $this->register($data);
            }

            throw new BackgroundException('用户信息未注册');
        }

        return $user;
    }

    /**
     * 记录登录信息.
     *
     * @param $id
     */
    private function recordLogin($id): void
    {
        /** @var User $user */
        $user = User::query()->find($id);

        // 记录连续登录
        if ($user->getRawOriginal('last_at') < Carbon::now()->startOfDay()->getTimestamp()) {
            $user->login_days = 1 === Carbon::now()->diff(Carbon::make($user->getRawOriginal('last_at')))->d
                ? $user->login_days + 1
                : $user->login_days;
            $user->max_login_days = max($user->max_login_days, $user->login_days);
        }

        $user->pre_at = $user->getRawOriginal('last_at');
        $user->login_ip = (int) ip2long(request()->getClientIp());
        $user->last_at = time();
        $user->save();
    }
}
