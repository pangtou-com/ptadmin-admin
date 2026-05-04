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

use PTAdmin\Foundation\Exceptions\ServiceException;

/**
 * @property string $title
 * @property string $name
 * @property int    $system_config_group_id
 * @property int    $weight
 * @property string $type
 * @property string $intro
 * @property array  $extra
 * @property string $value
 * @property string $default_val
 */
class SystemConfig extends \PTAdmin\Foundation\Database\Models\AbstractModel
{
    protected $table = 'system_configs';
    protected $fillable = ['title', 'name', 'system_config_group_id', 'weight', 'type', 'intro', 'extra', 'value', 'default_val'];
    protected $appends = ['extra_value'];
    protected $casts = ['extra' => 'array'];

    /**
     * 关联分组.
     */
    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SystemConfigGroup::class, 'system_config_group_id', 'id');
    }

    /**
     * 将换行文本格式的 options 转为统一存储结构。
     *
     * 支持：
     * - `key=value`
     * - 单行纯文本，自动使用顺序索引作为 key
     * - 已经是数组结构时直接归一化
     *
     * @param mixed $val
     */
    public function setExtraAttribute($val): void
    {
        if (null === $val || '' === $val) {
            $this->attributes['extra'] = json_encode(['options' => [], 'meta' => []], JSON_UNESCAPED_UNICODE);

            return;
        }

        if (\is_array($val) && array_key_exists('meta', $val)) {
            $meta = \is_array($val['meta']) ? $val['meta'] : [];
            $options = $this->normalizeExtraOptions($val['options'] ?? []);

            $this->attributes['extra'] = json_encode([
                'options' => $options,
                'meta' => $meta,
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if (\is_array($val) && !$this->shouldNormalizeExtraAsOptions($val)) {
            $this->attributes['extra'] = json_encode([
                'options' => [],
                'meta' => $val,
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $extraOptions = $this->normalizeExtraOptions($val);

        $this->attributes['extra'] = json_encode(['options' => $extraOptions, 'meta' => []], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param mixed $val
     *
     * @return array<string, string>
     */
    private function normalizeExtraOptions($val): array
    {
        $extraOptions = [];
        $options = \is_array($val) ? ($val['options'] ?? $val) : preg_split('/\r\n|\r|\n/', (string) $val);

        foreach ((array) $options as $key => $option) {
            if (\is_array($option)) {
                $normalizedKey = (string) ($option['value'] ?? $key);
                $normalizedValue = (string) ($option['label'] ?? $option['name'] ?? $normalizedKey);
            } else {
                $line = trim((string) $option);
                if ('' === $line) {
                    continue;
                }

                $parts = explode('=', $line, 2);
                $normalizedKey = 1 === \count($parts) ? (string) $key : trim($parts[0]);
                $normalizedValue = 1 === \count($parts) ? $line : trim($parts[1]);
            }

            if ('' === $normalizedKey) {
                $normalizedKey = (string) $key;
            }

            if (isset($extraOptions[$normalizedKey])) {
                throw new ServiceException(__('ptadmin::background.config_option_duplicate'));
            }

            $extraOptions[$normalizedKey] = $normalizedValue;
        }

        return $extraOptions;
    }

    /**
     * @param array<string|int, mixed> $val
     */
    private function shouldNormalizeExtraAsOptions(array $val): bool
    {
        if ([] === $val) {
            return true;
        }

        if (array_key_exists('options', $val)) {
            return true;
        }

        foreach ($val as $key => $item) {
            if (\is_array($item)) {
                return true;
            }

            if (\is_int($key)) {
                continue;
            }

            if (\is_string($item)) {
                continue;
            }

            return false;
        }

        return true;
    }

    public function getExtraValueAttribute(): string
    {
        $extra = $this->extra;
        $extraValue = [];
        if (isset($extra['options']) && \is_array($extra['options'])) {
            foreach ($extra['options'] as $key => $value) {
                $extraValue[] = $key.'='.$value;
            }
        }

        return implode("\n", $extraValue);
    }
}
