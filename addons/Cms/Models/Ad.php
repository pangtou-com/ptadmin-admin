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

namespace Addon\Cms\Models;

use Illuminate\Support\Facades\Cache;
use PTAdmin\Admin\Models\AbstractModel;

/**
 * @property int    $ad_space_id
 * @property string $title
 * @property string $ad_type
 * @property string $context
 * @property string $link_url
 * @property string $link_target
 * @property string $link_rel
 * @property string $alt_text
 * @property string $title_text
 * @property string $sort_order
 * @property string $display_type
 * @property string $start_at
 * @property string $end_at
 * @property string $audit_status
 * @property string $audit_remark
 * @property string $audit_time
 * @property string $auditor_id
 * @property string $status
 */
class Ad extends AbstractModel
{
    public const CACHE_ALL_KEY = 'ads_all';
    protected $table = 'cms_ads';

    protected $appends = [];

    public function getStartAtAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    public function getEndAtAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    public function space(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AdSpace::class, 'id', 'ad_space_id');
    }

    /**
     * 缓存广告表数据.
     *
     * @return array
     */
    public static function cacheAllData(): array
    {
        $data = self::query()->where('status', 1)
            ->get()
            ->map(function ($item) {
                $item->url = url("/ad-detail/{$item->id}");

                return $item;
            })->toArray();
        $results = [];
        foreach ($data as $item) {
            $results[$item['ad_position_id']][] = $item;
        }

        Cache::put(self::CACHE_ALL_KEY, serialize($results));

        return $results;
    }

    /**
     * 获取缓存数据.
     *
     * @return array|mixed
     */
    public static function getAllCacheData()
    {
        if (Cache::has(self::CACHE_ALL_KEY)) {
            $data = Cache::get(self::CACHE_ALL_KEY);
            $data = @unserialize($data);
            if (false !== $data) {
                return $data;
            }
        }

        return self::cacheAllData();
    }
}
