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

namespace PTAdmin\Admin\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PTAdmin\Easy\Easy;
use PTAdmin\Admin\Models\SystemConfig;
use PTAdmin\Admin\Models\SystemConfigGroup;
use PTAdmin\Admin\Support\ConfigRuleValidator;
use PTAdmin\Foundation\Exceptions\ServiceException;
use PTAdmin\Support\Enums\StatusEnum;

class SystemConfigService
{
    private const CACHE_KEY = 'systemConfig';

    /**
     * 返回某个配置分组的表单协议与当前值。
     *
     * 这里约定：
     * 1. 一级分组负责业务归类，如 system / payment / login
     * 2. 二级分组负责配置页签，如 basic / upload / oauth
     * 3. 具体 SystemConfig 记录是真正的字段定义
     *
     * @param int $id 二级分组 ID
     *
     * @return array<string, mixed>
     */
    public function section(int $id): array
    {
        [, $section] = $this->resolveSectionContext($id);
        $configs = $section->configs()
            ->orderBy('weight', 'desc')
            ->orderBy('id')
            ->get();

        return [
            'section' => [
                'id' => $section->id,
                'name' => $section->name,
                'title' => $section->title,
                'intro' => $section->intro,
                'extra' => $section->extra,
            ],
            'schema' => $this->buildSectionBlueprint($section, $configs->all()),
            'values' => $this->resolveSectionValues($configs->all()),
        ];
    }

    /**
     * 保存某个配置页签下的值。
     *
     * 接口允许两种提交方式：
     * 1. 直接平铺字段：{ "site_title": "PTAdmin" }
     * 2. 包裹在 values 中：{ "values": { "site_title": "PTAdmin" } }
     *
     * 未提交的字段保持原值，避免局部保存时误清空其他配置。
     *
     * @param int                $id
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function saveSection(int $id, array $data): array
    {
        [, $section] = $this->resolveSectionContext($id);
        $configs = $section->configs()
            ->orderBy('weight', 'desc')
            ->orderBy('id')
            ->get();

        /** @var mixed $payload */
        $payload = Arr::get($data, 'values', $data);
        if (!\is_array($payload)) {
            throw new ServiceException(__('ptadmin::background.config_value_invalid'));
        }
        
        DB::transaction(function () use ($configs, $payload): void {
            /** @var SystemConfig $config */
            foreach ($configs as $config) {
                if (!array_key_exists($config->name, $payload)) {
                    continue;
                }

                $this->assertSystemConfigValueAllowed($config, $payload[$config->name]);
                $config->value = $this->serializeSystemConfigValue($config, $payload[$config->name]);
                $config->save();
            }
        });

        self::updateSystemConfigCache();

        return $this->resolveSectionValues(
            $section->configs()
                ->orderBy('weight', 'desc')
                ->orderBy('id')
                ->get()
                ->all()
        );
    }

    /**
     * 通过分组名称获取分组数据.
     *
     * @param mixed $default
     *
     * @return array|mixed
     */
    public static function group(string $name, $default = null)
    {
        $group = SystemConfigGroup::query()
            ->with(['children', 'children.configs'])
            ->where('name', $name)
            ->first();
        if (!$group) {
            return $default;
        }

        $groupData = $group->toArray();
        $children = $groupData['children'];
        $data = [];
        foreach ($children as $child) {
            $sectionValues = [];
            if ($child['configs'] && \count($child['configs']) > 0) {
                foreach ($child['configs'] as $item) {
                    $sectionValues[$item['name']] = self::resolveRuntimeValue($item['type'] ?? 'text', $item['value'] ?? null);
                }
            }
            $data[$child['name']] = $sectionValues;
        }

        return $data;
    }

    /**
     * 获取系统配置信息.
     *
     * @param $key
     * @param $default
     *
     * @return null|array|mixed
     */
    public static function value($key, $default = null)
    {
        if (\is_string($key)) {
            return self::valueByPath($key, $default);
        }
        if (\is_array($key)) {
            $return = [];
            foreach ($key as $k => $item) {
                $return[$item] = self::valueByPath(\is_int($k) ? $item : $k, $default);
            }

            return $return;
        }

        return $default;
    }

    /**
     * 返回允许公开输出到前端界面的系统配置。
     *
     * @return array<string, mixed>
     */
    public static function public(): array
    {
        $results = [];
        $groups = SystemConfigGroup::query()
            ->whereNull('addon_code')
            ->where('status', StatusEnum::ENABLE)
            ->where('parent_id', '>', 0)
            ->with('configs')
            ->orderBy('weight', 'desc')
            ->orderBy('id')
            ->get();

        /** @var SystemConfigGroup $group */
        foreach ($groups as $group) {
            $parent = SystemConfigGroup::query()->find((int) $group->parent_id);
            if (null === $parent) {
                continue;
            }

            /** @var SystemConfig $config */
            foreach ($group->configs as $config) {
                $meta = (array) (((array) ($config->extra ?? []))['meta'] ?? []);
                if ('public' !== strtolower(trim((string) ($meta['expose'] ?? 'private')))) {
                    continue;
                }

                $path = sprintf('%s.%s.%s', $parent->name, $group->name, $config->name);
                $results[$path] = self::resolveRuntimeValue((string) $config->type, $config->value);
            }
        }

        return $results;
    }

    /**
     * 根据 key 路径获取指定系统配置信息.
     *
     * @param string $key
     * @param null   $default
     *
     * @return array|mixed
     */
    public static function valueByPath(string $key, $default = null)
    {
        $data = self::getSystemConfigCache();
        if (null === $data) {
            return $default;
        }
        $keyArr = explode('.', $key);
        if (3 === \count($keyArr)) {
            return data_get($data, $key, $default);
        }
        $first = reset($keyArr);
        $group = $data['__group_names__'][$first] ?? null;
        if (null === $group) {
            return $default;
        }
        if ($group !== $first) {
            return data_get($data, "{$group}.{$key}", $default);
        }

        return data_get($data, $key, $default);
    }

    /**
     * 获取系统配置缓存.
     *
     * @return null|array|mixed
     */
    public static function getSystemConfigCache()
    {
        if (Cache::has(self::CACHE_KEY)) {
            $data = Cache::get(self::CACHE_KEY);
            if (null !== $data) {
                return $data;
            }
        }

        return self::updateSystemConfigCache();
    }

    /**
     * 更新系统配置缓存.
     *
     * @return null|array
     */
    public static function updateSystemConfigCache(): ?array
    {
        $configGroups = SystemConfigGroup::query()
            ->where('status', StatusEnum::ENABLE)
            ->with('configs')->orderBy('parent_id')->get()->toArray();
        if (0 === \count($configGroups)) {
            return null;
        }
        $maps = array_to_map($configGroups, 'id', 'name');
        $keys = $data = [];
        foreach ($configGroups as $configGroup) {
            $parentId = (int) ($configGroup['parent_id'] ?? 0);
            $keys[$configGroup['name']] = $maps[$parentId] ?? $configGroup['name'];
            if (null !== $configGroup['configs'] && 0 !== $parentId) {
                $parent = $maps[$parentId] ?? null;
                if (null === $parent) {
                    continue;
                }
                $settings = [];
                foreach ($configGroup['configs'] as $item) {
                    $settings[$item['name']] = self::resolveRuntimeValue($item['type'] ?? 'text', $item['value'] ?? null);
                }
                $data[$parent][$configGroup['name']] = $settings;
            }
        }

        $data['__group_names__'] = $keys;

        Cache::forever(self::CACHE_KEY, $data);

        return $data;
    }

    /**
     * 解析二级配置分组上下文。
     *
     * @return array{0: SystemConfigGroup, 1: SystemConfigGroup}
     */
    private function resolveSectionContext(int $id): array
    {
        /** @var SystemConfigGroup|null $section */
        $section = SystemConfigGroup::query()
            ->where('status', StatusEnum::ENABLE)
            ->find($id);

        if (null === $section) {
            throw new ServiceException(__('ptadmin::background.config_group_not_exists'));
        }

        if (0 === $section->parent_id) {
            throw new ServiceException(__('ptadmin::background.config_section_required'));
        }
        
        return [null, $section];
    }

    /**
     * 将系统配置项元数据转换为 easy 可识别的 schema 蓝图。
     *
     * 配置系统本质上不是普通资源 CRUD，而是“单例表单”。
     * 因此这里只借用 easy 的 schema 编译、标准化和字段协议输出能力，
     * 最终仍由 system_configs 表保存值。
     *
     * @param array<int, SystemConfig> $settings
     *
     * @return array<string, mixed>
     */
    private function buildSectionBlueprint(SystemConfigGroup $section, array $settings): array
    {
        return [
            'name' => $section->name,
            'title' => $section->title,
            'fields' => array_map(function (SystemConfig $config): array {
                return $this->buildFieldSchema($config);
            }, $settings),
        ];
    }

    /**
     * 生成单个配置项的 easy 字段定义。
     *
     * 这里会把历史系统配置字段类型映射成 easy 的标准字段类型，
     * 并把 extra.options 收敛为统一的 options 协议。
     *
     * @return array<string, mixed>
     */
    private function buildFieldSchema(SystemConfig $config): array
    {
        $type = $this->normalizeFieldType($config->type);
        $field = [
            'name' => $config->name,
            'type' => $type,
            'label' => $config->title,
            'default' => self::resolveRuntimeValue($type, $config->default_val),
            'comment' => $config->intro ?? '',
            'metadata' => $this->resolveFieldMetadata($config),
        ];

        foreach ($field['metadata'] as $metaKey => $metaValue) {
            if (\in_array($metaKey, ['options_map', 'storage_type'], true)) {
                continue;
            }

            $field[$metaKey] = $metaValue;
        }

        $options = $this->resolveFieldOptions($config);
        if (0 !== \count($options)) {
            $field['options'] = $options;
        }

        return $field;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveFieldMetadata(SystemConfig $config): array
    {
        $extra = $config->extra;
        $meta = (array) ($extra['meta'] ?? []);

        if ([] !== (array) ($extra['options'] ?? [])) {
            $meta['options_map'] = (array) $extra['options'];
        }

        $meta['storage_type'] = $this->normalizeFieldType((string) $config->type);

        return $meta;
    }

    /**
     * 组装 section 当前值。
     *
     * @param array<int, SystemConfig> $settings
     *
     * @return array<string, mixed>
     */
    private function resolveSectionValues(array $settings): array
    {
        $values = [];
        foreach ($settings as $setting) {
            $values[$setting->name] = self::resolveRuntimeValue($setting->type, $setting->value);
        }

        return $values;
    }

    /**
     * 将配置项的原始字符串值转换为运行时值。
     *
     * 系统配置在数据库中主要以字符串保存，但业务读取时需要更贴近字段语义：
     * - switch / number -> int / float
     * - checkbox / json / cascader -> array
     * - 其他类型默认保持字符串
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private static function resolveRuntimeValue(string $type, $value)
    {
        if (null === $value) {
            return self::defaultRuntimeValue($type);
        }

        if (\is_array($value)) {
            return $value;
        }

        $normalizedType = (new self())->normalizeFieldType($type);
        $stringValue = \is_scalar($value) ? (string) $value : '';

        switch ($normalizedType) {
            case 'switch':
            case 'radio':
                return $value;
            case 'number':
                if ('' === $stringValue) {
                    return 'number' === $normalizedType ? 0 : "";
                }

                return false !== strpos($stringValue, '.') ? (float) $stringValue : (int) $stringValue;

            case 'checkbox':
            case 'json':
            case 'cascader':
                if ('' === $stringValue) {
                    return [];
                }

                $decoded = json_decode($stringValue, true);

                return \is_array($decoded) ? $decoded : [];

            default:
                return $stringValue;
        }
    }

    /**
     * 将请求值写回 SystemConfig.value。
     *
     * 保存时统一先做字段级归一化，再转换为数据库可存储格式，
     * 这样读取缓存与接口详情时可以得到稳定结果。
     *
     * @param mixed $value
     */
    private function serializeSystemConfigValue(SystemConfig $setting, $value)
    {
        $type = $this->normalizeFieldType($setting->type);

        switch ($type) {
            case 'switch':
            case 'radio':
                return $value;

            case 'number':
                if (null === $value || '' === $value) {
                    return '0';
                }

                return (string) $value;

            case 'checkbox':
            case 'key-value':
            case 'cascader':
                if (null === $value || '' === $value) {
                    return json_encode([], JSON_UNESCAPED_UNICODE);
                }

                if (\is_string($value)) {
                    $decoded = json_decode($value, true);
                    if (\is_array($decoded)) {
                        return json_encode($decoded, JSON_UNESCAPED_UNICODE);
                    }

                    return json_encode([$value], JSON_UNESCAPED_UNICODE);
                }

                return json_encode((array) $value, JSON_UNESCAPED_UNICODE);

            default:
                if (null === $value) {
                    return '';
                }

                return \is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * TODO 这个是临时解决方案，后期使用正式规则处理器来解决类型和赋值的问题
     * @param mixed $value
     */
    private function assertSystemConfigValueAllowed(SystemConfig $setting, $value): void
    {
        $type = $this->normalizeFieldType($setting->type);

        switch ($type) {
            case 'switch':
                if ($this->isBooleanLikeValue($value)) {
                    return;
                }

                throw new ServiceException(__('ptadmin::background.config_field_value_switch_invalid', ['name' => $setting->name]));

            case 'radio':
            case 'select':
                if (!\is_scalar($value) && null !== $value) {
                    throw new ServiceException(__('ptadmin::background.config_field_value_option_invalid', ['name' => $setting->name]));
                }

                $this->assertValueInFieldOptions($setting, null === $value ? '' : (string) $value);

                return;

            case 'checkbox':
                if (!\is_array($value)) {
                    throw new ServiceException(__('ptadmin::background.config_field_value_checkbox_invalid', ['name' => $setting->name]));
                }

                foreach ($value as $item) {
                    if (!\is_scalar($item) && null !== $item) {
                        throw new ServiceException(__('ptadmin::background.config_field_value_checkbox_invalid', ['name' => $setting->name]));
                    }

                    $this->assertValueInFieldOptions($setting, null === $item ? '' : (string) $item);
                }

                return;

            case 'json':
            case 'cascader':
                if (\is_array($value)) {
                    return;
                }

                if (\is_string($value)) {
                    $decoded = json_decode($value, true);
                    if (\is_array($decoded)) {
                        return;
                    }
                }

                throw new ServiceException(__('ptadmin::background.config_field_value_json_invalid', ['name' => $setting->name]));

            default:
                $this->assertScalarFieldMetaRules($setting, $value, $type);

                return;
        }
    }

    /**
     * 将历史字段类型映射为 easy 标准类型。
     */
    private function normalizeFieldType(string $type): string
    {
        $type = strtolower(trim($type));

        switch ($type) {
            case 'input':
            case 'string':
                return 'text';

            case 'editor':
            case 'content':
                return 'textarea';

            case 'int':
            case 'integer':
            case 'float':
            case 'amount':
                return 'number';

            case 'bool':
            case 'boolean':
            case 'status':
                return 'switch';

            case 'check':
                return 'checkbox';

            case 'picture':
            case 'avatar':
                return 'image';

            case 'array':
            case 'object':
                return 'json';

            default:
                return '' === $type ? 'text' : $type;
        }
    }

    /**
     * 转换配置项选项定义。
     *
     * SystemConfig.extra 内部仍然是 key => label 的历史结构，
     * 这里输出 easy 标准 options：[{ label, value }].
     *
     * @return array<int, array<string, mixed>>
     */
    private function resolveFieldOptions(SystemConfig $setting): array
    {
        $extra = $setting->extra;
        $options = $extra['options'] ?? [];
        if (!\is_array($options)) {
            return [];
        }

        $results = [];
        foreach ($options as $value => $label) {
            $results[] = [
                'label' => (string) $label,
                'value' => \is_int($value) ? (string) $value : $value,
            ];
        }

        return $results;
    }

    private function isBooleanLikeValue($value): bool
    {
        if (\is_bool($value)) {
            return true;
        }

        if (\is_int($value)) {
            return \in_array($value, [0, 1], true);
        }

        if (\is_string($value)) {
            return \in_array(strtolower(trim($value)), ['0', '1', 'true', 'false'], true);
        }

        return false;
    }

    private function assertValueInFieldOptions(SystemConfig $setting, string $value): void
    {
        $extra = $setting->extra;
        $options = $extra['options'] ?? [];
        if (!\is_array($options) || [] === $options) {
            return;
        }

        if (array_key_exists($value, $options)) {
            return;
        }

        $stringKeys = array_map(static function ($item): string {
            return (string) $item;
        }, array_keys($options));

        if (\in_array($value, $stringKeys, true)) {
            return;
        }

        throw new ServiceException(__('ptadmin::background.config_field_value_option_invalid', ['name' => $setting->name]));
    }

    /**
     * @param mixed $value
     */
    private function assertScalarFieldMetaRules(SystemConfig $setting, $value, string $type): void
    {
        if (\in_array($type, ['text', 'textarea', 'password'], true)) {
            if (\is_array($value) || \is_object($value)) {
                throw new ServiceException(__('ptadmin::background.config_field_value_scalar_invalid', ['name' => $setting->name]));
            }
        }

        $extra = $setting->extra;
        $meta = (array) ($extra['meta'] ?? []);
        if ([] === $meta) {
            return;
        }

        $stringValue = null === $value ? '' : (\is_scalar($value) ? (string) $value : '');
        $trimmedValue = trim($stringValue);

        if ( ($meta['required'] ?? false) && '' === $trimmedValue) {
            throw new ServiceException(__('ptadmin::background.config_field_required', ['name' => $setting->name]));
        }

        if ('' === $trimmedValue) {
            return;
        }

        if (isset($meta['min']) && is_numeric($meta['min']) && mb_strlen($stringValue) < (int) $meta['min']) {
            throw new ServiceException(__('ptadmin::background.config_field_min_invalid', ['name' => $setting->name, 'min' => (int) $meta['min']]));
        }

        if (isset($meta['max']) && is_numeric($meta['max']) && mb_strlen($stringValue) > (int) $meta['max']) {
            throw new ServiceException(__('ptadmin::background.config_field_max_invalid', ['name' => $setting->name, 'max' => (int) $meta['max']]));
        }

        $pattern = trim((string) ($meta['pattern'] ?? ''));
        if ('' !== $pattern && 1 !== @preg_match($pattern, $stringValue)) {
            throw new ServiceException(__('ptadmin::background.config_field_pattern_invalid', ['name' => $setting->name]));
        }
    }

    /**
     * 返回字段默认运行时值。
     *
     * @return array<int, mixed>|int|string
     */
    private static function defaultRuntimeValue(string $type)
    {
        $normalizedType = (new self())->normalizeFieldType($type);

        switch ($normalizedType) {
            case 'switch':
            case 'radio':
            case 'number':
                return 0;

            case 'checkbox':
            case 'json':
            case 'cascader':
                return [];
            case 'key-value':
                return (object)[];
            default:
                return '';
        }
    }

}
