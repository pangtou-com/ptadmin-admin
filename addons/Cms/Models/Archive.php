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

namespace Addon\Cms\Models;

use Addon\Cms\Enum\ArchiveStatusEnum;
use Addon\Cms\Enum\AttributeEnum;
use Addon\Cms\Enum\SEOEnum;
use Addon\Cms\Service\SeoService;
use Addon\Cms\Service\SeoUrlService;
use Illuminate\Database\Eloquent\SoftDeletes;
use PTAdmin\Admin\Models\AbstractModel;
use PTAdmin\Easy\Model\Mod;

/**
 * @property string $title.
 * @property string $subtitle.
 * @property int    $category_id.
 * @property int    $mod_id.
 * @property string $spider.
 * @property string $views.
 * @property numeric $price.
 * @property string $attribute.
 * @property string $seo_title.
 * @property string $seo_keyword.
 * @property string $seo_doc.
 * @property int    $is_comment.
 * @property int    $is_visitor.
 * @property int    $tag_id.
 * @property int    $status.
 * @property int    $weight.
 * @property int    $tread_num.
 * @property int    $praise_num.
 * @property int    $comment_num.
 * @property int    $collection_num.
 * @property string $author.
 * @property string $nickname.
 * @property int    $admin_id.
 * @property int    $user_id.
 * @property int    $extend_id.
 * @property string $extend_table_name.
 * @property string $cover.
 * @property array  $picture.
 */
class Archive extends AbstractModel
{
    use SoftDeletes;

    protected $table = 'cms_archives';

    protected $fillable = [
        'title','subtitle', 'views', 'price', 'attribute', 'seo_title', 'seo_keyword', 'seo_doc', 'is_comment', 'tag_id', 'is_visitor',
        'category_id', 'mod_id', 'spider', 'status', 'weight', 'author', 'tread_num', 'praise_num', 'comment_num','related_category_id',
        'collection_num', 'nickname', 'cover', 'picture',
    ];

    protected $appends = ['attribute_text', 'status_text', 'l_url', 'c_url', 'a_url', 'h5_a_url'];
    protected $casts = ['picture' => 'array'];

    public static function addViews($id)
    {
        self::query()->where('id', $id)->increment('views');
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public static function countByCategoryId($id): int
    {
        return self::query()->where('category_id', $id)->count();
    }

    /**
     * 文章栏目列表地址
     *
     * @return string
     */
    public function getLUrlAttribute(): string
    {
        return (new SeoService(new SeoUrlService()))->url(SEOEnum::LIST, $this->attributes);
    }

    /**
     * 文章频道分类地址
     *
     * @return string
     */
    public function getCUrlAttribute(): string
    {
        return (new SeoService(new SeoUrlService()))->url(SEOEnum::CHANNEL, $this->attributes);
    }

    /**
     * 文章访问地址
     *
     * @return string
     */
    public function getAUrlAttribute(): string
    {
        return (new SeoService(new SeoUrlService()))->url(SEOEnum::DETAIL, $this->attributes);
    }

    /**
     * 文章访问地址
     *
     * @return string
     */
    public function getH5AUrlAttribute(): string
    {
        return (new SeoService(new SeoUrlService()))->url(SEOEnum::DETAIL, $this->attributes, 'h5');
    }

    /**
     * 推荐属性.
     *
     * @return array
     */
    public function getAttributeTextAttribute(): array
    {
        $val = $this->attributes['attribute'] ?? 0;

        return AttributeEnum::compareAll((int) $val);
    }

    public function getAttributeAttribute(): array
    {
        $val = $this->attributes['attribute'] ?? 0;

        return AttributeEnum::compareAllKey((int) $val);
    }

    public function setAttributeAttribute($val): void
    {
        $this->attributes['attribute'] = AttributeEnum::getSummaryValue($val);
    }

    /**
     * 文章状态
     *
     * @return string
     */
    public function getStatusTextAttribute(): string
    {
        $val = $this->attributes['status'] ?? 0;

        return ArchiveStatusEnum::getDescription($val);
    }

    public function content(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ArchiveContent::class, 'archive_id', 'id');
    }

    public function mod(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Mod::class, 'mod_id', 'id');
    }
}
