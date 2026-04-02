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

namespace Addon\Cms\Enum;

use Addon\Cms\Http\Controllers\Home\ArchiveController;
use PTAdmin\Admin\Enum\AbstractEnum;

class SEOEnum extends AbstractEnum
{
    /** @var int 频道路由 */
    public const CHANNEL = 1;

    /** @var int 列表路由 */
    public const LIST = 2;

    /** @var int 详情路由 */
    public const DETAIL = 3;

    /** @var int 专题路由 */
    public const TOPIC = 4;

    /** @var int 单页 */
    public const SINGLE = 5;

    /** @var int 标签 */
    public const TAG = 6;

    public static function getDescription($value): string
    {
        return [
            self::CHANNEL => '频道路由',
            self::LIST => '列表路由',
            self::DETAIL => '详情路由',
            self::TOPIC => '专题路由',
            self::SINGLE => '单页',
            self::TAG => '标签',
        ][$value] ?? '未归类';
    }

    /**
     * 获取支持参数信息.
     *
     * @param null|mixed $value
     *
     * @return array
     */
    public static function getSupportParams($value = null): array
    {
        $maps = [
            self::CHANNEL => [
                'prefix' => 'channel',
                'params' => ['category' => '栏目分类', 'category_id' => '栏目分类ID', 'mod' => '模型名称', 'mod_id' => '模型ID'],
                'title_params' => ['category_title' => '栏目标题', 'page' => '页码', 'site_title' => '站点标题'],
                'default_url' => '{category_id}/{mod_id}.html',
                'default_title' => '{site_title}-{category_title} 第{page}页',
                'required' => ['category_id', 'category'],
            ],
            self::LIST => [
                'prefix' => 'lists',
                'params' => ['category' => '栏目分类', 'category_id' => '栏目分类ID', 'mod' => '模型名称', 'mod_id' => '模型ID'],
                'title_params' => ['category_title' => '栏目标题', 'page' => '页码', 'site_title' => '站点标题'],
                'default_url' => '{category_id}/{mod_id}/{page}.html',
                'default_title' => '{site_title}-{category_title} 第{page}页',
                'required' => ['category_id', 'category'],
            ],
            self::DETAIL => [
                'prefix' => 'detail',
                'params' => ['category' => '栏目分类', 'id' => '文章ID'],
                'title_params' => ['category_title' => '栏目标题', 'mod_title' => '模型标题', 'site_title' => '站点标题', 'title' => '文章标题'],
                'default_url' => '{id}.html',
                'default_title' => '{site_title}-{title}',
                'required' => ['id'],
            ],
            self::TOPIC => [
                'prefix' => 'topic',
                'params' => ['topic' => '专题名称', 'topic_id' => '专题ID'],
                'title_params' => ['topic_title' => '专题标题', 'page' => '页码', 'site_title' => '站点标题'],
                'default_url' => '{topic_id}.html',
                'default_title' => '{site_title}-{topic_title}',
            ],
            self::SINGLE => [
                'prefix' => 'single',
                'params' => ['category' => '栏目分类', 'category_id' => '栏目分类ID', 'mod' => '模型名称', 'mod_id' => '模型ID'],
                'title_params' => ['category_title' => '栏目标题',  'site_title' => '站点标题', 'title' => '文章标题'],
                'default_url' => '{category_id}/{mod_id}.html',
                'default_title' => '{site_title}-{title}',
                'required' => ['category_id', 'category'],
            ],
            self::TAG => [
                'prefix' => 'tag',
                'params' => ['tag_id' => '标签ID', 'page' => '页码'],
                'title_params' => ['tag_title' => '标签标题', 'page' => '页码', 'site_title' => '站点标题'],
                'default_url' => '{tag_id}/{page}.html',
                'default_title' => '{site_title}-{tag_title} 第{page}页',
            ],
        ];
        if (null !== $value) {
            return $maps[$value] ?? [];
        }

        return $maps;
    }

    /**
     * 获取相关字段的相关信息.
     *
     * @return array
     */
    public static function allKeys(): array
    {
        return [
            'category' => [
                'table' => 'cms_categories',
                'id' => 'dir_name',
                'where' => ['status', '=', 1],
                'isMust' => true,
            ],
            'category_id' => [
                'table' => 'cms_categories',
                'id' => 'id',
                'where' => '[1-9][0-9]*',
                'isMust' => true,
            ],
            'mod' => [
                'table' => 'mods',
                'id' => 'mod_name',
                'where' => ['status', '=', 1],
                'isMust' => true,
            ],
            'mod_id' => [
                'table' => 'mods',
                'id' => 'id',
                'where' => '[1-9][0-9]*',
                'isMust' => true,
            ],
            'topic' => [
                'table' => 'cms_topics',
                'id' => 'url',
                'where' => ['status', '=', 1],
                'isMust' => true,
            ],
            'topic_id' => [
                'table' => 'cms_topics',
                'id' => 'id',
                'where' => '[1-9][0-9]*',
                'isMust' => true,
            ],
            'tag_id' => [
                'table' => 'tags',
                'id' => 'id',
                'where' => '[1-9][0-9]*',
                'isMust' => true,
            ],
            'id' => [
                'table' => 'self_table',
                'id' => 'id',
                'where' => '[1-9][0-9]*',
                'isMust' => true,
            ],
            'page' => [
                'table' => 'self_table',
                'id' => 'id',
                'where' => '[1-9][0-9]*',
                'isMust' => false,
            ],
        ];
    }

    public static function getValueByLowerKey($lowerKey): ?int
    {
        return [
            self::getLowerKey(self::CHANNEL) => self::CHANNEL,
            self::getLowerKey(self::LIST) => self::LIST,
            self::getLowerKey(self::DETAIL) => self::DETAIL,
            self::getLowerKey(self::TOPIC) => self::TOPIC,
            self::getLowerKey(self::SINGLE) => self::SINGLE,
            self::getLowerKey(self::TAG) => self::TAG,
        ][$lowerKey] ?? null;
    }

    public static function getRouteConfig(): array
    {
        return [
            self::LIST => [ArchiveController::class, 'lists'],
            self::CHANNEL => [ArchiveController::class, 'channel'],
            self::DETAIL => [ArchiveController::class, 'detail'],
            self::TAG => [ArchiveController::class, 'tag'],
            self::SINGLE => [ArchiveController::class, 'single'],
            self::TOPIC => [ArchiveController::class, 'topic'],
        ];
    }
}
