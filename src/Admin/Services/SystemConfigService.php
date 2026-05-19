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
use PTAdmin\Admin\Models\SystemConfig;
use PTAdmin\Admin\Models\SystemConfigGroup;
use PTAdmin\Easy\Easy;
use PTAdmin\Foundation\Exceptions\BackgroundException;
use PTAdmin\Foundation\Exceptions\ServiceException;
use PTAdmin\Support\Enums\StatusEnum;

class SystemConfigService
{
    private const CACHE_KEY = 'systemConfig';
    private const CACHE_SECTIONS_KEY = '__sections__';
    private const CACHE_FIELDS_KEY = '__fields__';
    private const CACHE_PUBLIC_FIELDS_KEY = '__public_fields__';

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
        $section = $this->resolveSectionContext($id);
        $configs = $section->configs()
            ->orderBy('sort', 'desc')
            ->orderBy('id')
            ->get();

        $schema = $this->buildSectionBlueprint($section, $configs->all());
        
        return [
            'schema' => $schema,
            'fields' => $configs->toArray(),
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
     */
    public function saveSection(int $id, array $data)
    {
        $section = $this->resolveSectionContext($id);
        $configs = $section->configs()
            ->orderBy('sort', 'desc')
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
                $config->value = $this->fieldHandle($config)->toStorage($payload[$config->name]);
                $config->save();
            }
        });

        self::updateSystemConfigCache();
    }
    
    
    public function store($data)
    {
        $group = new SystemConfig();
        $group->fill($data);
        $group->save();
    }
    
    public function edit(int $id, $data)
    {
        /** @var  SystemConfig $group */
        $group = SystemConfig::query()->where('id', $id)->firstOrFail();
        if ($group->is_system && $data['name'] !== $group->name) {
            throw new BackgroundException("当前字段不允许编辑，字段标识");
        }
        $group->fill($data);
        $group->save();
    }
    
    public function detail(int $id)
    {
        return SystemConfig::query()->where('id', $id)->firstOrFail();
    }
    
    public function delete($id)
    {
        /** @var SystemConfig $dao */
        $dao = SystemConfig::query()->where('id', $id)->firstOrFail();
        if ($dao->is_system) {
            throw new BackgroundException("系统预设字段不允许删除");
        }
        $dao->delete();
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
     * 获取插件配置信息.
     *
     * @param string $addonCode
     * @param $key
     * @param mixed $default
     *
     * @return mixed
     */
    public static function addonValue(string $addonCode, $key, $default = null)
    {
        $addonCode = trim($addonCode);
        if ('' === $addonCode) {
            return $default;
        }

        if (\is_string($key)) {
            return self::valueByPath(sprintf('%s::%s', $addonCode, $key), $default);
        }

        if (\is_array($key)) {
            $return = [];
            foreach ($key as $k => $item) {
                $path = \is_int($k) ? $item : $k;
                $return[$item] = self::valueByPath(sprintf('%s::%s', $addonCode, $path), $default);
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
        $data = self::getSystemConfigCache();
        if (null === $data) {
            return [];
        }

        return (array) ($data[self::CACHE_PUBLIC_FIELDS_KEY] ?? []);
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

        $normalizedKey = trim($key);
        if ('' === $normalizedKey) {
            return $default;
        }

        if (array_key_exists($normalizedKey, $data[self::CACHE_SECTIONS_KEY] ?? [])) {
            return $data[self::CACHE_SECTIONS_KEY][$normalizedKey];
        }

        if (array_key_exists($normalizedKey, $data[self::CACHE_FIELDS_KEY] ?? [])) {
            return $data[self::CACHE_FIELDS_KEY][$normalizedKey];
        }

        return $default;
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
            ->with('configs')
            ->orderBy('type')
            ->orderByDesc('sort')
            ->orderBy('id')
            ->get();
        if (0 === \count($configGroups)) {
            return null;
        }

        $data = [
            self::CACHE_SECTIONS_KEY => [],
            self::CACHE_FIELDS_KEY => [],
            self::CACHE_PUBLIC_FIELDS_KEY => [],
        ];

        /** @var SystemConfigGroup $configGroup */
        foreach ($configGroups as $configGroup) {
            $sectionKey = self::buildSectionKey((string) $configGroup->name, $configGroup->addon_code);
            $settings = [];

            /** @var SystemConfig $item */
            foreach ($configGroup->configs as $item) {
                $runtimeValue = self::resolveRuntimeValue($item);
                $settings[$item->name] = $runtimeValue;
                $data[self::CACHE_FIELDS_KEY][sprintf('%s.%s', $sectionKey, $item->name)] = $runtimeValue;

                if ('public' === strtolower(trim((string) $configGroup->access))) {
                    $data[self::CACHE_PUBLIC_FIELDS_KEY][sprintf('%s.%s', $configGroup->name, $item->name)] = $runtimeValue;
                }
            }

            $data[self::CACHE_SECTIONS_KEY][$sectionKey] = $settings;
        }

        Cache::forever(self::CACHE_KEY, $data);

        return $data;
    }

    private static function buildSectionKey(string $groupName, ?string $addonCode = null): string
    {
        $groupName = trim($groupName);
        $addonCode = null === $addonCode ? null : trim($addonCode);

        if (null !== $addonCode && '' !== $addonCode) {
            return sprintf('%s::%s', $addonCode, $groupName);
        }

        return $groupName;
    }

    /**
     * 解析二级配置分组上下文。
     *
     */
    private function resolveSectionContext(int $id): SystemConfigGroup
    {
        /** @var SystemConfigGroup|null $section */
        $section = SystemConfigGroup::query()
            ->where('status', StatusEnum::ENABLE)
            ->find($id);

        if (null === $section) {
            throw new ServiceException(__('ptadmin::background.config_group_not_exists'));
        }
        
        return $section;
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
            'form' => [
                'wrapper' => "default",
                'col' => 24
            ],
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
        $schema = $this->fieldHandle($config)->schema();
        
        if (isset($schema['rules'])) {
            unset($schema['rules']);
        }
        
        return $schema;
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
            $values[$setting->name] = self::resolveRuntimeValue($setting);
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
     * @return mixed
     */
    private static function resolveRuntimeValue($setting)
    {
        $service = new self();
        $field = $service->fieldHandle($setting);
    
        return $field->toRuntime(data_get($setting, "value"));
    }
    
    /**
     * @param SystemConfig|array<string, mixed> $setting
     */
    private function fieldHandle($setting)
    {
        return Easy::field($this->buildEasyFieldSchema($setting));
    }

    /**
     * @param SystemConfig|array<string, mixed> $setting
     *
     * @return array<string, mixed>
     */
    private function buildEasyFieldSchema($setting): array
    {
        $extra = $setting instanceof SystemConfig ? $setting->extra : (array) ($setting['extra'] ?? []);
    
        return [
            'name' => data_get($setting, "name"),
            'type' => data_get($setting, "type"),
            'label' => data_get($setting, 'title'),
            'defaultValue' => data_get($setting, 'default_val'),
            'help' => data_get($setting, 'intro'),
            'options' => $this->normalizeOptions($extra),
        ];
    }

    /**
     * @param mixed $extra
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeOptions($extra): array
    {
        if (!\is_array($extra) || [] === $extra) {
            return [];
        }

        $type = strtolower(trim((string) ($extra['type'] ?? '')));
        if ('textarea' === $type) {
            return $this->normalizeTextareaOptions($extra['content'] ?? null);
        }

        if (\in_array($type, ['key-value', 'key_value'], true)) {
            return $this->normalizeItemOptions($extra['items'] ?? []);
        }

        $options = $extra['options'] ?? [];
        if (!\is_array($options) || [] === $options) {
            return [];
        }

        $normalized = [];
        foreach ($options as $value => $label) {
            if (\is_array($label) && array_key_exists('label', $label) && array_key_exists('value', $label)) {
                $normalized[] = $label;

                continue;
            }

            $normalized[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        return $normalized;
    }

    /**
     * @param mixed $content
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeTextareaOptions($content): array
    {
        if (!\is_string($content) || '' === trim($content)) {
            return [];
        }

        $results = [];
        foreach (preg_split('/\r\n|\r|\n/', $content) ?: [] as $index => $line) {
            $line = trim((string) $line);
            if ('' === $line) {
                continue;
            }

            $segments = explode('=', $line, 2);
            if (2 === \count($segments)) {
                $results[] = [
                    'label' => trim((string) $segments[1]),
                    'value' => trim((string) $segments[0]),
                ];

                continue;
            }

            $results[] = [
                'label' => $line,
                'value' => $index,
            ];
        }

        return $results;
    }

    /**
     * @param mixed $items
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeItemOptions($items): array
    {
        if (!\is_array($items) || [] === $items) {
            return [];
        }

        $results = [];
        foreach ($items as $item) {
            if (!\is_array($item) || !array_key_exists('value', $item)) {
                continue;
            }

            $results[] = [
                'label' => $item['label'] ?? $item['value'],
                'value' => $item['value'],
            ];
        }

        return $results;
    }

}
