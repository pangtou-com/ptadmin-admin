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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PTAdmin\Admin\Models\Traits\ModelField;
use PTAdmin\Admin\Models\Traits\SearchTrait;
use PTAdmin\Admin\Utils\SystemAuth;

abstract class AbstractModel extends Model
{
    use ModelField;
    use SearchTrait;

    /** @var string 日期列表的存储格式 */
    protected $dateFormat = 'U';

    /** @var array 属性黑名单，名单内容无法填充 */
    protected $guarded = ['id'];

    /**
     * 批量新增数据.
     *
     * @param array $data
     */
    public static function batchAdd(array $data): void
    {
        DB::table((new static())->getTable())->insert($data);
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
     * 数据填充.
     *
     * @param array $attributes
     *
     * @return $this
     */
    public function fill(array $attributes): self
    {
        // 排除掉填充时数据为null的情况
        foreach ($attributes as $key => $attribute) {
            if (null === $attribute) {
                unset($attributes[$key]);
            }
        }

        return parent::fill($attributes);
    }

    public function getCreatedAtAttribute()
    {
        return date('Y-m-d H:i:s', $this->attributes['created_at']);
    }

    public function getUpdatedAtAttribute()
    {
        return date('Y-m-d H:i:s', $this->attributes['updated_at']);
    }

    /**
     * 设置每页显示数量.
     */
    public function getPerPage(): int
    {
        $limit = (int) (request()->get('limit', 20));
        if ($limit) {
            return $limit;
        }

        return parent::getPerPage();
    }

    /**
     * 模型更新和新增时分别增加创建人、更新人.
     */
    protected static function booted(): void
    {
        // 新增前操作
        static::creating(function ($model): void {
            if (isset($model->fillable['creator_id'])) {
                $model->creator_id = SystemAuth::check() ? SystemAuth::user()->id : 0;
            }
        });

        // 修改前操作
        static::updating(function ($model): void {
            if (isset($model->fillable['updater_id'])) {
                $model->updater_id = SystemAuth::check() ? SystemAuth::user()->id : 0;
            }
        });
    }
}
