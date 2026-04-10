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
use PTAdmin\Foundation\Exceptions\ServiceException;
use PTAdmin\Support\Enums\StatusEnum;

class SystemConfigService
{
    /**
     * 存储配置值信息.
     *
     * @param $data
     */
    public function save($data): void
    {
        DB::transaction(function () use ($data): void {
            $ids = $data['ids'] ?? [];
            $configure = SystemConfig::query()->whereIn('system_config_group_id', $ids)->get()->toArray();
            if (0 === \count($configure)) {
                return;
            }
            $parent = SystemConfigGroup::query()->select(['id', 'name'])->whereIn('id', $ids)->get()->toArray();
            $parent = array_to_map($parent, 'id', 'name');

            foreach ($configure as $item) {
                $name = ($parent[$item['system_config_group_id']] ?? '').'_'.$item['name'];
                SystemConfig::query()
                    ->where('id', $item['id'])
                    ->where('name', $item['name'])->update(['value' => $data[$name] ?? '']);
            }
        });

        self::updateSystemConfigCache();
    }

    public function page($search = []): array
    {
        $filterMap = SystemConfig::query();

        $filterMap->orderBy('system_config_group_id');
        $filterMap->orderBy('weight', 'desc');
        $filterMap->with('category');

        return $filterMap->paginate()->toArray();
    }

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
        [$group, $section] = $this->resolveSectionContext($id);
        $configs = $section->configs()
            ->orderBy('weight', 'desc')
            ->orderBy('id')
            ->get();

        return [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'title' => $group->title,
                'intro' => $group->intro,
            ],
            'section' => [
                'id' => $section->id,
                'name' => $section->name,
                'title' => $section->title,
                'intro' => $section->intro,
            ],
            'schema' => $this->buildSectionBlueprint($group, $section, $configs->all()),
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
            throw new ServiceException('配置值格式错误');
        }

        DB::transaction(function () use ($configs, $payload): void {
            /** @var SystemConfig $config */
            foreach ($configs as $config) {
                if (!array_key_exists($config->name, $payload)) {
                    continue;
                }

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
     * @param $name
     * @param null|mixed $default
     *
     * @return array|mixed
     */
    public static function byGroupName($name, $default = null)
    {
        $cate = SystemConfigGroup::query()->with(['children', 'children.configs'])->where('name', $name)->first();
        if (!$cate) {
            return $default;
        }
        $cate = $cate->toArray();
        $children = $cate['children'];
        $data = [];
        foreach ($children as $child) {
            $temp = [];
            if ($child['configs'] && \count($child['configs']) > 0) {
                foreach ($child['configs'] as $item) {
                    $temp[$item['name']] = self::resolveRuntimeValue($item['type'] ?? 'text', $item['value'] ?? null);
                }
            }
            $data[$child['name']] = $temp;
        }

        return $data;
    }

    public function store(array $data): void
    {
        try {
            DB::transaction(function () use ($data): void {
                $filter = new SystemConfig();
                $filter->fill($data)->save();
            });
        } catch (\Exception $e) {
            throw new ServiceException($e->getMessage());
        }
        self::updateSystemConfigCache();
    }

    /**
     * 删除配置项定义。
     *
     * @param array<int, int|string> $ids
     */
    public function delete(array $ids): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (0 === \count($ids)) {
            return;
        }

        DB::transaction(function () use ($ids): void {
            SystemConfig::query()->whereIn('id', $ids)->delete();
        });

        self::updateSystemConfigCache();
    }

    /**
     * 编辑配置信息.
     *
     * @param $id
     * @param array $data
     */
    public function edit($id, array $data): void
    {
        try {
            DB::transaction(function () use ($id, $data): void {
                $filter = SystemConfig::query()->findOrFail($id);
                $filter->fill($data)->save();
            });
        } catch (\Exception $e) {
            throw new ServiceException($e->getMessage());
        }
        self::updateSystemConfigCache();
    }

    /**
     * 获取系统配置信息.
     *
     * @param $key
     * @param $default
     *
     * @return null|array|mixed
     */
    public static function getSystemConfig($key, $default = null)
    {
        if (\is_string($key)) {
            return self::config($key, $default);
        }
        if (\is_array($key)) {
            $return = [];
            foreach ($key as $k => $item) {
                $return[$item] = self::config(\is_int($k) ? $item : $k, $default);
            }

            return $return;
        }

        return $default;
    }

    /**
     * 根据key值获取指定系统配置信息.
     *
     * @param string $key
     * @param null   $default
     *
     * @return array|mixed
     */
    public static function config(string $key, $default = null)
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
        if (Cache::has('systemConfig')) {
            $data = Cache::get('systemConfig');
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

        Cache::forever('systemConfig', $data);

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
            throw new ServiceException('配置分组不存在');
        }

        if (0 === (int) $section->parent_id) {
            throw new ServiceException('请通过二级分组读取配置项');
        }

        /** @var SystemConfigGroup|null $group */
        $group = SystemConfigGroup::query()
            ->where('status', StatusEnum::ENABLE)
            ->find((int) $section->parent_id);

        if (null === $group) {
            throw new ServiceException('上级配置分组不存在');
        }

        return [$group, $section];
    }

    /**
     * 将 Setting 元数据转换为 easy 可识别的 schema 蓝图。
     *
     * 配置系统本质上不是普通资源 CRUD，而是“单例表单”。
     * 因此这里只借用 easy 的 schema 编译、标准化和字段协议输出能力，
     * 最终仍由 system_configs 表保存值。
     *
     * @param array<int, SystemConfig> $settings
     *
     * @return array<string, mixed>
     */
    private function buildSectionBlueprint(SystemConfigGroup $group, SystemConfigGroup $section, array $settings): array
    {
        if (0 === \count($settings)) {
            return [
                'resource' => [
                    'name' => $this->resolveSchemaName($group->name, $section->name),
                    'title' => $section->title,
                    'module' => 'ptadmin_admin',
                    'table' => [],
                    'primary_key' => 'id',
                    'comment' => $section->intro ?? '',
                ],
                'views' => [
                    'table' => [],
                    'form' => [
                        'layout' => 'vertical',
                    ],
                ],
                'layout' => [],
                'fields' => [],
                'relations' => [],
                'permissions' => [],
                'charts' => [],
            ];
        }

        $schema = [
            'name' => $this->resolveSchemaName($group->name, $section->name),
            'title' => $section->title,
            'module' => 'ptadmin_admin',
            'form' => [
                'layout' => 'vertical',
            ],
            'fields' => array_map(function (SystemConfig $config): array {
                return $this->buildFieldSchema($config);
            }, $settings),
        ];

        return Easy::schema($schema)->blueprint();
    }

    /**
     * 生成单个配置项的 easy 字段定义。
     *
     * 这里会把历史 Setting.type 映射成 easy 的标准字段类型，
     * 并把 extra.options 收敛为统一的 options 协议。
     *
     * @return array<string, mixed>
     */
    private function buildFieldSchema(SystemConfig $config): array
    {
        $type = $this->normalizeFieldType((string) $config->type);
        $field = [
            'name' => $config->name,
            'type' => $type,
            'label' => $config->title,
            'default' => self::resolveRuntimeValue($type, $config->default_val),
            'comment' => $config->intro ?? '',
        ];

        $options = $this->resolveFieldOptions($config);
        if (0 !== \count($options)) {
            $field['options'] = $options;
        }

        return $field;
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
            $values[$setting->name] = self::resolveRuntimeValue((string) $setting->type, $setting->value);
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
            case 'number':
                if ('' === $stringValue) {
                    return 'number' === $normalizedType ? 0 : 0;
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
     * 将请求值写回 Setting.value。
     *
     * 保存时统一先做字段级归一化，再转换为数据库可存储格式，
     * 这样读取缓存与接口详情时可以得到稳定结果。
     *
     * @param mixed $value
     */
    private function serializeSystemConfigValue(SystemConfig $setting, $value): string
    {
        $type = $this->normalizeFieldType((string) $setting->type);

        switch ($type) {
            case 'switch':
            case 'radio':
                return (string) (int) $value;

            case 'number':
                if (null === $value || '' === $value) {
                    return '0';
                }

                return (string) $value;

            case 'checkbox':
            case 'json':
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

    /**
     * 构造一个稳定的 schema 名称，避免和业务资源命名冲突。
     */
    private function resolveSchemaName(string $group, string $section): string
    {
        return "{$group}_{$section}_settings";
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

            default:
                return '';
        }
    }

}
