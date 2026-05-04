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

/**
 * @property int $id
 * @property int|null $admin_id
 * @property string $login_account
 * @property int $login_at
 * @property string|null $login_ip
 * @property string $status
 * @property string|null $reason
 * @property string|null $user_agent
 */
class AdminLoginLog extends \PTAdmin\Foundation\Database\Models\AbstractModel
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_INVALID_CREDENTIALS = 'invalid_credentials';
    public const STATUS_USER_NOT_FOUND = 'user_not_found';
    public const STATUS_DISABLED = 'disabled';
    public const STATUS_CAPTCHA_INVALID = 'captcha_invalid';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_FAILED = 'failed';

    protected $table = 'admin_login_logs';

    protected $fillable = ['admin_id', 'login_account', 'login_at', 'login_ip', 'status', 'reason', 'user_agent'];

    public function getLoginAtAttribute()
    {
        return date('Y-m-d H:i:s', (int) $this->attributes['login_at']);
    }

    public function getLoginIpAttribute(): ?string
    {
        $value = $this->attributes['login_ip'] ?? null;
        if (null === $value || '' === (string) $value) {
            return null;
        }

        return (string) $value;
    }

    public function admin(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }
}
