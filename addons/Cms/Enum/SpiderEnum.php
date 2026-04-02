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

use PTAdmin\Admin\Enum\AbstractEnum;

/**
 * 爬虫类型.
 */
class SpiderEnum extends AbstractEnum
{
    /** @var int 百度 */
    public const TYPE_BAIDU = 1 << 0;
    /** @var int 谷歌 */
    public const TYPE_GOOGLE = 1 << 1;
    /** @var int 必应 */
    public const TYPE_BING = 1 << 2;
    /** @var int 头条 */
    public const TYPE_TOU_TIAO = 1 << 3;
    /** @var int 华为 */
    public const TYPE_HUA_WEI = 1 << 4;
    /** @var int 雅虎 */
    public const TYPE_YA = 1 << 5;
    /** @var int 搜狗 */
    public const TYPE_SOU_GOU = 1 << 6;
    /** @var int 360 */
    public const TYPE_360 = 1 << 7;

    public static function getDescription($value): string
    {
        return [
            self::TYPE_BAIDU => '百度',
            self::TYPE_GOOGLE => '谷歌',
            self::TYPE_BING => '必应',
            self::TYPE_TOU_TIAO => '头条',
            self::TYPE_HUA_WEI => '华为',
            self::TYPE_YA => '雅虎',
            self::TYPE_SOU_GOU => '搜狗',
            self::TYPE_360 => '360',
        ][$value] ?? '';
    }
}
