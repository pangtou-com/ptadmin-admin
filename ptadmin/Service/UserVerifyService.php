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

use Carbon\Carbon;
use PTAdmin\Admin\Exceptions\BackgroundException;
use PTAdmin\Admin\Models\UserVerify;

/**
 * 用户验证信息发送类型.
 */
class UserVerifyService
{
    /** @var int 短信信息 */
    public const TYPE_SMS = 0;

    /** @var int 邮箱信息 */
    public const TYPE_EMAIL = 1;

    /** 使用验证场景 */
    /** @var int 注册验证 */
    public const SCENE_REGISTER = 0;

    /** @var int 登录验证 */
    public const SCENE_LOGIN = 1;

    /** @var int 找回密码 */
    public const SCENE_FORGET = 2;

    /** @var int 绑定 */
    public const SCENE_BIND = 3;

    /** @var int 解除绑定 */
    public const SCENE_UNBIND = 4;
    /** 使用验证场景 END */

    /**
     * @var float|int 有效验证时间 默认为 5分钟
     */
    private $overtime = 60 * 5;

    /**
     * @var int 有效验证次数
     */
    private $verifyNum = 5;

    /**
     * 发送验证信息.
     *
     * @param int    $type   验证类型，0 短信，1 邮箱
     * @param int    $scene  验证场景，0 注册，1 登录，2 找回密码，3 绑定，4 解除绑定
     * @param string $target 验证目标
     */
    public static function sendVerifyCode(string $target, int $scene = self::SCENE_REGISTER, int $type = self::TYPE_EMAIL): string
    {
        $obj = new self();
        $time = Carbon::now()->subSeconds($obj->getOvertime($type, $scene))->timestamp;
        $ip = request()->getClientIp();
        $obj->checkIP($ip, $time);
        $obj->checkTarget($target, $type, $scene, $time);

        $model = new UserVerify();
        $model->fill([
            'type' => $type,
            'scene' => $scene,
            'target' => $target,
            'send_param' => ['code' => random(6, true)],
            'ip' => (int) ip2long($ip),
            'user_id' => $obj->getUserId($scene),
            'send_status' => 1, // TODO 这里模拟认为发送成功，正式数据应为0
            'send_at' => time(),
        ]);
        $model->save();
        // 发送验证码
        // TODO 发送验证码
        return $model->send_param['code'];
    }

    /**
     * 校验验证码.
     *
     * @param string $target
     * @param string $code
     * @param int    $scene
     * @param int    $type
     */
    public static function verifyCode(string $target, string $code, int $scene = self::SCENE_REGISTER, int $type = self::TYPE_EMAIL): void
    {
        $obj = new self();
        $time = Carbon::now()->subSeconds($obj->getOvertime($type, $scene))->timestamp;
        $model = UserVerify::query()
            ->where('target', $target)
            ->where('type', $type)
            ->where('scene', $scene)
            ->orderBy('id', 'desc')->first();
        if (!$model) {
            throw new BackgroundException('验证码错误');
        }
        $model->increment('verify_num');
        if ($model->verify_num > $obj->verifyNum || $model->status > 0) {
            throw new BackgroundException('验证码已失效');
        }
        if ($model->send_at < $time) {
            throw new BackgroundException('验证码已过期');
        }
        if (data_get($model->send_param, 'code') !== $code) {
            throw new BackgroundException('验证码错误');
        }

        $model->status = 1;
        $model->save();
    }

    /**
     * 验证目标是否允许继续发送验证码
     *
     * @param string $target
     * @param int    $type
     * @param int    $scene
     * @param $time
     */
    private function checkTarget(string $target, int $type, int $scene, $time): void
    {
        $exists = UserVerify::query()
            ->where('target', $target)
            ->where('type', $type)
            ->where('scene', $scene)
            ->where('created_at', '>=', $time)->exists();
        if ($exists) {
            throw new BackgroundException('请不要频繁操作');
        }
    }

    /**
     * 校验当前用户提交的IP是否允许继续发送验证码
     *
     * @param $ip
     * @param $time
     */
    private function checkIP($ip, $time): void
    {
        $ip = ip2long($ip);
        if (false === $ip) {
            return;
        }
        $count = UserVerify::query()
            ->where('ip', $ip)
            ->where('created_at', '>=', $time)->count();
        if ($count >= $this->verifyNum * 2) {
            throw new BackgroundException('请不要频繁操作');
        }
    }

    /**
     * 获取验证码，超时验证时间.
     * 预设方法后期考虑针对不同的使用场景提供不同的超时时间.
     *
     * @param $type
     * @param $scene
     *
     * @return float|int
     */
    private function getOvertime($type, $scene)
    {
        return $this->overtime;
    }

    /**
     * 获取用户ID，部分场景下需要获取用户的ID信息.
     *
     * @param int $scene
     *
     * @return int
     */
    private function getUserId(int $scene): int
    {
        $allow = [self::SCENE_BIND, self::SCENE_UNBIND];
        if (\in_array($scene, $allow, true)) {
            return auth('web')->id();
        }

        return 0;
    }
}
