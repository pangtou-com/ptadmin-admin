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
use PTAdmin\Admin\Utils\SystemAuth;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int    $id
 * @property string $username
 * @property string $nickname
 * @property string $password
 * @property string $mobile
 * @property string $avatar
 * @property string $login_at
 * @property string $login_ip
 * @property int    $is_founder
 * @property int    $status
 */
class System extends Authenticate
{
    use HasRoles;
    use SoftDeletes;

    protected $appends = ['role_id', 'role_name'];
    protected $hidden = ['password'];
    protected $guard_name;
    protected $fillable = ['username', 'nickname', 'mobile', 'email', 'avatar', 'login_at', 'login_ip', 'status'];

    public function __construct()
    {
        $this->guard_name = SystemAuth::getGuard();
        parent::__construct();
    }

    public function guardName()
    {
        return SystemAuth::getGuard();
    }

    public function getRoleIdAttribute()
    {
        $role = $this->roles()->pluck('id')->toArray();
        $role = reset($role);

        return $role ?? 0;
    }

    public function getRoleNameAttribute()
    {
        $role = $this->roles()->pluck('title')->toArray();
        $role = reset($role);

        return $role ?? '';
    }

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

    public function getCreatedAtAttribute()
    {
        return date('Y-m-d H:i:s', $this->attributes['created_at']);
    }

    public function getUpdatedAtAttribute()
    {
        return date('Y-m-d H:i:s', $this->attributes['updated_at']);
    }

    public function getLoginAtAttribute()
    {
        if (isset($this->attributes['login_at']) && $this->attributes['login_at']) {
            return date('Y-m-d H:i:s', $this->attributes['login_at']);
        }

        return '';
    }
}
