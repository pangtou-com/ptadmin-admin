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

namespace PTAdmin\Admin\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticate;
use PTAdmin\Admin\Models\Traits\SearchTrait;

/**
 * @property int    $id             ID
 * @property string $username       用户名
 * @property string $nickname       用户昵称
 * @property string $password       登录密码
 * @property string $salt           盐
 * @property string $email          邮箱
 * @property string $mobile         手机
 * @property string $avatar         头像
 * @property string $level          等级
 * @property string $gender         性别
 * @property string $birthday       生日
 * @property string $bio            签名
 * @property string $money          余额
 * @property string $score          积分余额
 * @property int    $login_days     连续登录天数
 * @property int    $max_login_days 最大连续登录天数
 * @property string $pre_at         上次登录时间
 * @property string $last_at        最新登录时间
 * @property string $login_ip       最新登录IP
 * @property string $join_ip        加入IP
 * @property string $join_at        加入时间
 * @property int    $status         账户状态
 */
class User extends Authenticate
{
    use SearchTrait;
    use SoftDeletes;

    protected $hidden = ['password', 'salt', 'remember_token'];
    protected $fillable = ['username', 'nickname', 'password', 'salt', 'email', 'mobile', 'avatar', 'bio'];

    /**
     * 获取当前时间.
     */
    public function freshTimestamp(): int
    {
        return time();
    }

    /**
     * 避免转换时间戳为时间字符串.
     *
     * @param int $value
     *
     * @return int
     */
    public function fromDateTime($value): int
    {
        return $value;
    }

    /**
     * 从数据库获取的为获取时间戳格式.
     */
    public function getDateFormat(): string
    {
        return 'U';
    }

    public function getAvatarAttribute(): string
    {
        if ($this->exists) {
            return $this->attributes['avatar'] ?? user_avatar($this->attributes['id']);
        }

        return '';
    }

    public function getCreatedAtAttribute()
    {
        return date('Y-m-d H:i:s', $this->attributes['created_at']);
    }

    public function getUpdatedAtAttribute()
    {
        return date('Y-m-d H:i:s', $this->attributes['updated_at']);
    }

    public function getJoinAtAttribute($value)
    {
        return $value ? date('Y-m-d', $value) : '';
    }

    public function getPreAtAttribute($value)
    {
        return $value ? date('Y-m-d', $value) : '';
    }

    public function getLastAtAttribute($value)
    {
        return $value ? date('Y-m-d', $value) : '';
    }

    public function getJoinIpAttribute($value): string
    {
        return $value ? long2ip($value) : '';
    }
}
