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

namespace Addon\Cms\Providers;

use Addon\Cms\Service\Manager\NavExtendManager;
use Addon\Cms\Service\MenuItemService;
use Addon\Cms\Service\ArchiveExportService;
use Addon\Cms\Service\CategoryExportService;
use Addon\Cms\Service\TopicExportService;
use PTAdmin\Addon\Providers\BaseAddonService;

class CmsServiceProvider extends BaseAddonService
{
    // 插件名称，用于标识插件，必须唯一
    protected $addonName = 'cms';

    // 插件路径, 一般情况下使用插件名称作为路径，首字母大写
    protected $addonPath = 'Cms';

    /** @var mixed 暴露指令调用方法 */
    protected $export = [
        'arc' => [
            'class' => ArchiveExportService::class,
            'method' => 'handle',
        ],
        'single' => [
            'class' => ArchiveExportService::class,
            'method' => 'single_detail',
        ],
        'detail' => [
            'class' => ArchiveExportService::class,
            'method' => 'detail',
        ],
        'category' => [
            'class' => CategoryExportService::class,
            'method' => 'handle',
        ],
        'topic' => [
            'class' => TopicExportService::class,
            'method' => 'handle',
        ],
        'lists' => [
            'class' => CategoryExportService::class,
            'method' => 'lists',
        ],
        'cLists' => [
            'class' => CategoryExportService::class,
            'method' => 'categoryLists',
        ],
        'getCategoryByPid' => [
            'class' => CategoryExportService::class,
            'method' => 'getCategoryListByParentId',
        ],
        'page' => [
            'class' => CategoryExportService::class,
            'method' => 'page',
        ],
        'prev' => [
            'class' => ArchiveExportService::class,
            'method' => 'prev',
        ],
        'next' => [
            'class' => ArchiveExportService::class,
            'method' => 'next',
        ],
        'hot' => [
            'class' => ArchiveExportService::class,
            'method' => 'hot',
        ],
        'recommended' => [
            'class' => ArchiveExportService::class,
            'method' => 'recommended',
        ],
        'headlines' => [
            'class' => ArchiveExportService::class,
            'method' => 'headlines',
        ],
        'featured' => [
            'class' => ArchiveExportService::class,
            'method' => 'featured',
        ],
        'exclusive' => [
            'class' => ArchiveExportService::class,
            'method' => 'exclusive',
        ],
        'index' => [
            'class' => ArchiveExportService::class,
            'method' => 'index',
        ],
        'nav' => [
            'class' => MenuItemService::class,
            'method' => 'index',
        ],
    ];

    /**
     * 服务注册，系统启动时会自动调用，注意只能注册不依赖于其他服务的服务
     */
    public function register(): void
    {
    }
}
