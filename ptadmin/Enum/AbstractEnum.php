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

namespace PTAdmin\Admin\Enum;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PTAdmin\Admin\Exceptions\BackgroundException;

abstract class AbstractEnum
{
    protected static $labelMaps = [];
    private static $constCacheArray;

    public static function getKeys(): Collection
    {
        return self::getConstants()->keys();
    }

    public static function getLowerKeys(): Collection
    {
        return self::getConstants()->keys()->map(function ($item) {
            return strtolower($item);
        });
    }

    public static function getValues(): Collection
    {
        return self::getConstants()->values();
    }

    public static function getKey($value): string
    {
        return self::getConstants()->search($value);
    }

    public static function getValue(string $key)
    {
        return self::getConstants()->get($key);
    }

    /**
     * 获取字段转译.
     *
     * @param $value
     *
     * @return string
     */
    public static function getDescription($value): string
    {
        $key = static::getLowerKey($value);
        $className = explode('\\', static::class);
        $name = strtolower(Str::snake(array_pop($className)));
        $name = Str::replace('_enum', '', $name);
        $prefix = '';
        // 判断是否属于应用枚举
        if ('addon' === strtolower($className[0])) {
            $prefix = strtolower(Str::snake($className[1])).'::';
        }

        return __("{$prefix}common.{$name}.{$key}");
    }

    public static function getLowerKey($value): string
    {
        return strtolower(self::getKey($value));
    }

    public static function getMaps(): array
    {
        $maps = [];
        foreach (self::getValues() as $value) {
            $maps[$value] = static::getDescription($value);
        }

        return $maps;
    }

    public static function getMapToOptions(): array
    {
        return array_options(self::getMaps());
    }

    private static function getConstants(): Collection
    {
        if (null === self::$constCacheArray) {
            self::$constCacheArray = [];
        }

        $calledClass = static::class;

        if (!\array_key_exists($calledClass, self::$constCacheArray)) {
            try {
                $reflect = new \ReflectionClass($calledClass);
                self::$constCacheArray[$calledClass] = $reflect->getConstants();
            } catch (\ReflectionException $e) {
                throw new BackgroundException('方法生成错误');
            }
        }

        return collect(self::$constCacheArray[$calledClass]);
    }
}
