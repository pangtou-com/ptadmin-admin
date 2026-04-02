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

class AttributeEnum extends AbstractEnum
{
    /** @var int 热门 */
    public const HOT = 1 << 0;

    /** @var int 推荐 Recommended */
    public const RECOMMENDED = 1 << 1;

    /** @var int 头条 headlines */
    public const HEADLINES = 1 << 2;

    /** @var int 最新 */
    public const LAST = 1 << 3;

    /** @var int 精选 Featured */
    public const FEATURED = 1 << 4;

    /** @var int 独家 Exclusive */
    public const EXCLUSIVE = 1 << 5;

    /** @var int 图片 image */
    public const IMAGE = 1 << 6;

    /** @var int 首页推荐 INDEX */
    public const INDEX = 1 << 7;

    public static function getAllDescription()
    {
        return [
            self::HOT => '热门',
            self::RECOMMENDED => '推荐',
            self::HEADLINES => '头条',
            self::LAST => '最新',
            self::FEATURED => '精选',
            self::EXCLUSIVE => '独家',
            self::IMAGE => '图片',
            self::INDEX => '首页推荐',
        ];
    }

    /**
     * 返回计算结果.
     *
     * @param $val
     *
     * @return int
     */
    public static function getSummaryValue($val): int
    {
        if (\is_string($val)) {
            $val = explode(',', $val);
        }
        if (!\is_array($val)) {
            return 0;
        }
        $a = 0;
        foreach ($val as $v) {
            $a = $a | $v;
        }

        return $a;
    }

    public static function compareAll(int $val): array
    {
        if (0 === $val) {
            return [];
        }
        $arr = [];
        foreach (self::getMaps() as $key => $v) {
            if (self::compare($val, $key)) {
                $arr[] = $v;
            }
        }

        return $arr;
    }

    /**
     * 根据key值获取对应的值
     *
     * @param int $val
     *
     * @return array
     */
    public static function compareAllKey(int $val): array
    {
        if (0 === $val) {
            return [];
        }
        $arr = [];
        foreach (self::getMaps() as $key => $v) {
            if (self::compare($val, $key)) {
                $arr[] = $key;
            }
        }

        return $arr;
    }

    public static function compare($val, $val1): bool
    {
        return ($val & $val1) > 0;
    }
}
