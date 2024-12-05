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

use PTAdmin\Admin\Exceptions\ServiceException;

/**
 * @property string $title
 * @property string $name
 * @property int    $setting_group_id
 * @property int    $weight
 * @property string $type
 * @property string $intro
 * @property array  $extra
 * @property string $value
 * @property string $default_val
 */
class Setting extends AbstractModel
{
    protected $fillable = ['title', 'name', 'setting_group_id', 'weight', 'type', 'intro', 'extra', 'value', 'default_val'];
    protected $appends = ['extra_value'];
    protected $casts = ['extra' => 'array'];

    /**
     * 关联分组.
     */
    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SettingGroup::class, 'setting_group_id', 'id');
    }

    public function setExtraAttribute($val): void
    {
        $extraOptions = [];
        $allOptions = explode("\n", $val);
        foreach ($allOptions as $key => $option) {
            $option = explode('=', $option);
            $key = 1 === \count($option) ? $key : $option[0];
            $value = 1 === \count($option) ? $option[0] : $option[1];
            if (isset($extraOptions[$key])) {
                throw new ServiceException('配置项键名重复，请规范填写');
            }
            $extraOptions[$key] = $value;
        }
        $this->attributes['extra'] = json_encode(['options' => $extraOptions]);
    }

    public function getExtraValueAttribute(): string
    {
        $extra = $this->extra;
        $extra_value = [];
        if (isset($extra['options']) && '' !== $extra['options']) {
            foreach ($extra['options'] as $key => $value) {
                $extra_value[] = $key.'='.$value;
            }
        }

        return implode("\n", $extra_value);
    }
}
