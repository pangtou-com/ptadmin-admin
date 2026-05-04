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

use PTAdmin\Admin\Models\SystemConfig;
use PTAdmin\Admin\Models\SystemConfigGroup;
use PTAdmin\Admin\Support\ConfigRuleValidator;
use PTAdmin\Foundation\Exceptions\ServiceException;

class SystemConfigGroupService
{
    /**
     * 安装时数据初始化.
     *
     * @param array<int, array<string, mixed>> $data
     */
    public static function installInitialize(array $data, int $parentId = 0): void
    {
        foreach ($data as $item) {
            self::assertGroupHierarchyAllowed($item, $parentId);
            self::assertGroupExtraAllowed((array) ($item['extra'] ?? []));

            $groupData = $item;
            $groupData['parent_id'] = $parentId;
            unset($groupData['children'], $groupData['fields']);

            /** @var SystemConfigGroup $model */
            $model = SystemConfigGroup::query()->updateOrCreate(['name' => $item['name']], $groupData);

            if (isset($item['children']) && \count($item['children']) > 0) {
                self::installInitialize($item['children'], $model->id);

                continue;
            }

            if (isset($item['fields']) && \count($item['fields']) > 0) {
                self::assertUniqueFieldNames((array) $item['fields']);
                foreach ($item['fields'] as $field) {
                    $fieldType = strtolower(trim((string) ($field['type'] ?? '')));
                    self::assertFieldTypeAllowed($fieldType);
                    self::assertFieldExtraAllowed($fieldType, (array) ($field['extra'] ?? []));
                    $field['system_config_group_id'] = $model->id;
                    SystemConfig::query()->updateOrCreate(
                        [
                            'system_config_group_id' => $model->id,
                            'name' => $field['name'],
                        ],
                        $field
                    );
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function assertGroupHierarchyAllowed(array $item, int $parentId): void
    {
        if (0 === $parentId) {
            return;
        }

        $parent = SystemConfigGroup::query()->find($parentId);
        if (null === $parent) {
            return;
        }

        if ((int) $parent->parent_id > 0) {
            throw new ServiceException(__('ptadmin::background.config_group_depth_invalid'));
        }

        if (isset($item['children']) && \count((array) $item['children']) > 0) {
            throw new ServiceException(__('ptadmin::background.config_group_depth_invalid'));
        }
    }

    /**
     * @param array<string, mixed> $extra
     */
    private static function assertGroupExtraAllowed(array $extra): void
    {
        $layout = (array) ($extra['layout'] ?? []);
        if ([] === $layout) {
            return;
        }

        $mode = strtolower(trim((string) ($layout['mode'] ?? 'block')));
        if (!ConfigRuleValidator::isValidGroupLayoutMode($mode)) {
            throw new ServiceException(__('ptadmin::background.config_group_layout_invalid'));
        }
    }

    /**
     * @param array<string, mixed> $extra
     */
    private static function assertFieldExtraAllowed(string $type, array $extra): void
    {
        $meta = (array) ($extra['meta'] ?? []);
        if ([] === $meta) {
            return;
        }

        foreach (array_keys($meta) as $key) {
            if (!\is_string($key)) {
                continue;
            }

            $resolvedKey = strtolower(trim($key));
            if ('' === $resolvedKey) {
                continue;
            }

            if (!\in_array($resolvedKey, ConfigRuleValidator::supportedFieldMetaKeys(), true)) {
                throw new ServiceException(__('ptadmin::background.config_field_meta_key_invalid', ['key' => $resolvedKey]));
            }
        }

        $unsupportedKeys = ConfigRuleValidator::unsupportedMetaKeysForFieldType($type, $meta);
        if ([] !== $unsupportedKeys) {
            throw new ServiceException(__('ptadmin::background.config_field_meta_unsupported', [
                'type' => $type,
                'key' => $unsupportedKeys[0],
            ]));
        }

        $expose = strtolower(trim((string) ($meta['expose'] ?? 'private')));
        if (!ConfigRuleValidator::isValidExposeMode($expose)) {
            throw new ServiceException(__('ptadmin::background.config_field_expose_invalid'));
        }

        $invalidMeta = ConfigRuleValidator::invalidMetaValueTypes($meta);
        if ([] !== $invalidMeta) {
            throw new ServiceException(__('ptadmin::background.config_field_meta_value_invalid', ['key' => reset($invalidMeta)]));
        }
    }

    private static function assertFieldTypeAllowed(string $type): void
    {
        if (ConfigRuleValidator::isValidFieldType($type)) {
            return;
        }

        throw new ServiceException(__('ptadmin::background.config_field_type_invalid'));
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     */
    private static function assertUniqueFieldNames(array $fields): void
    {
        $names = [];
        foreach ($fields as $field) {
            if (!\is_array($field)) {
                continue;
            }

            $name = trim((string) ($field['name'] ?? ''));
            if ('' === $name) {
                continue;
            }

            if (isset($names[$name])) {
                throw new ServiceException(__('ptadmin::background.config_field_name_duplicate', ['name' => $name]));
            }

            $names[$name] = true;
        }
    }
}
