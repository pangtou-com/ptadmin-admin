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

use Addon\Cms\Enum\SEOEnum;
use Addon\Cms\Service\SeoService;
use Addon\Cms\Service\SeoUrlService;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use PTAdmin\Admin\Enum\StatusEnum;
use PTAdmin\Admin\Models\AbstractModel;
use PTAdmin\Admin\Service\SettingService;
use PTAdmin\Easy\Model\Mod;

/**
 * @property string $title
 * @property string $subtitle
 * @property int    $parent_id
 * @property int[]  $parent_ids
 * @property string $type
 * @property string $alias
 * @property int    $mod_id
 * @property string $cover
 * @property string $icon
 * @property int    $document_num
 * @property int    $is_nav
 * @property int    $status
 * @property string $description
 * @property string $external_link
 * @property int    $weight
 * @property string $seo_title
 * @property string $seo_keyword
 * @property string $seo_doc
 * @property string $template_list
 * @property string $template_detail
 * @property string $template_channel
 */
class Category extends AbstractModel
{
    use SoftDeletes;

    protected $table = 'cms_categories';

    protected $appends = ['l_url', 'c_url'];

    protected $fillable = [
        'title', 'type', 'subtitle', 'alias', 'parent_id', 'parent_ids', 'cover', 'icon', 'document_num', 'is_related', 'description',
        'weight', 'external_link', 'seo_title', 'seo_keyword', 'seo_doc', 'template_list', 'template_detail', 'template_channel',
    ];

    protected $guarded = ['status', 'mod_id'];
    protected $casts = ['parent_ids' => 'array'];

    /**
     * 列表页链接.
     *
     * @return string
     */
    public function getLUrlAttribute(): string
    {
        // return (new SeoService(new SeoUrlService()))->url(SEOEnum::LIST, $this->attributes);
        return '';
    }

    /**
     * 频道页链接.
     *
     * @return string
     */
    public function getCUrlAttribute(): string
    {
        // return (new SeoService(new SeoUrlService()))->url(SEOEnum::CHANNEL, $this->attributes);
        return '';
    }

    /**
     * 单页栏目.
     *
     * @param $id
     *
     * @return null|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object
     */
    public static function single($id)
    {
        return self::query()->where('id', $id)->where('is_single', 1)->first();
    }

    /**
     * 获取层级数据.
     *
     * @return array
     */
    public static function getLevels(): array
    {
        $model = new self();

        return whenBlank($model->getAllCachedData('levels'), function () use ($model) {
            $value = [];
            infinite_level(self::getAllData(['status' => 1]), $value);
            $model->setAllCachedData('levels', $value);

            return $value;
        });
    }

    /**
     * 获取树形结构数据.
     *
     * @return mixed
     */
    public static function getTrees()
    {
        $model = new self();

        return whenBlank($model->getAllCachedData('trees'), function () use ($model) {
            $value = infinite_tree(self::getAllData(['status' => 1]));
            $model->setAllCachedData('trees', $value);

            return $value;
        });
    }

    /**
     * 获取整表数据.
     *
     * @param mixed $where
     * @param mixed $order
     *
     * @return array
     */
    public static function getAllData($where = [], $order = ['weight' => 'desc']): array
    {
        $filterMap = self::query()->where($where);

        if (\count($order) > 0) {
            foreach ($order as $key => $value) {
                $filterMap->orderBy($key, $value);
            }
        }

        return $filterMap->get()->toArray();
    }

    /**
     * 根据栏目id获取seo.
     *
     * @param $id
     *
     * @return array
     */
    public static function getCategorySeoById($id): array
    {
        $seo = SettingService::getSetting('seo') ?? [];
        $category = self::query()->where('id', $id)->first();
        $category = $category ? $category->toArray() : [];

        return [
            'seo_title' => $category['seo_title'] && '' !== $category['seo_title'] ? $category['seo_title'] : ($seo['seo_title'] ?? ($category['title'] ?? '')),
            'seo_keyword' => $category['seo_keyword'] && '' !== $category['seo_keyword'] ? $category['seo_keyword'] : ($seo['seo_title'] ?? ($category['title'] ?? '')),
            'seo_doc' => $category['seo_doc'] && '' !== $category['seo_doc'] ? trim(mb_substr(str_replace(['&nbsp;', "\n"], '', strip_tags($category['seo_doc'])), 0, 100)) : ($seo['seo_title'] ?? ($category['title'] ?? '')),
        ];
    }

    /**
     * 获取缓存整表数据的key.
     *
     * @param $type
     *
     * @return string
     */
    public static function getCacheAllKey($type): string
    {
        $table = (new self())->getTable();

        return "{$table}_all_{$type}";
    }

    public static function getCategoryOptions(): array
    {
        return array_to_options(self::query()->where('status', 1)->orderBy('weight')->get()->toArray());
    }

    public static function getQuestionRelateCategoryOptions(): array
    {
        return array_to_options(self::query()->whereIn('id', [18, 19, 21, 22])->where('status', StatusEnum::ENABLE)->orderBy('weight')->get()->toArray());
    }

    public function mod(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Mod::class, 'mod_id', 'id');
    }

    protected static function booted(): void
    {
        foreach (['saved', 'deleted'] as $event) {
            static::registerModelEvent($event, function ($model): void {
                $model->setAllCachedData('trees', null);
                $model->setAllCachedData('levels', null);
            });
        }
    }

    /**
     * 获取缓存整表数据.
     *
     * @param $type
     *
     * @return null|array
     */
    protected function getAllCachedData($type): ?array
    {
        if (Cache::has(self::getCacheAllKey($type))) {
            $data = Cache::get(self::getCacheAllKey($type));
            if (false !== $data) {
                return $data;
            }
        }

        return null;
    }

    /**
     * 设置缓存整表数据.
     *
     * @param string     $type
     * @param null|array $data
     */
    protected function setAllCachedData(string $type, ?array $data): void
    {
        if (null === $data) {
            Cache::forget(self::getCacheAllKey($type));

            return;
        }
        Cache::put(self::getCacheAllKey($type), $data, 3600);
    }
}
