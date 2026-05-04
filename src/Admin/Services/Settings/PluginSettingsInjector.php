<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Settings;

use PTAdmin\Admin\Models\SystemConfig;
use PTAdmin\Admin\Models\SystemConfigGroup;
use PTAdmin\Admin\Support\ConfigRuleValidator;

class PluginSettingsInjector
{
    /**
     * @param array<string, mixed> $addon
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $sectionDefinition
     */
    public function ensureSection(string $code, array $addon, array $settings, array $sectionDefinition): SystemConfigGroup
    {
        $rootName = sprintf('addon_%s', $code);
        $sectionKey = (string) ($sectionDefinition['key'] ?? 'basic');
        $sectionGroupName = sprintf('addon_%s_%s', $code, $sectionKey);
        $injectionStrategy = strtolower(trim((string) data_get($settings, 'injection.strategy', 'merge')));

        /** @var SystemConfigGroup $root */
        $root = SystemConfigGroup::query()->firstOrCreate(
            [
                'addon_code' => $code,
                'name' => $rootName,
            ],
            [
                'title' => (string) ($addon['title'] ?? $addon['name'] ?? $code),
                'parent_id' => 0,
                'intro' => (string) ($addon['description'] ?? $addon['intro'] ?? ''),
                'extra' => [
                    'managed_by' => (string) ($sectionDefinition['managed_by'] ?? 'system'),
                ],
                'status' => 1,
                'weight' => 0,
            ]
        );

        /** @var ?SystemConfigGroup $existingSection */
        $existingSection = SystemConfigGroup::query()
            ->where('addon_code', $code)
            ->where('name', $sectionGroupName)
            ->first()
        ;

        if ('skip' === $injectionStrategy && null !== $existingSection) {
            return $existingSection->refresh();
        }

        /** @var SystemConfigGroup $section */
        $section = $existingSection ?? new SystemConfigGroup();
        if (!$section->exists) {
            $section->addon_code = $code;
            $section->name = $sectionGroupName;
        }
        if (0 === (int) $section->parent_id || (int) $section->parent_id !== (int) $root->id) {
            $section->parent_id = (int) $root->id;
        }

        $section->title = (string) ($sectionDefinition['title'] ?? $section->title);
        $section->intro = (string) ($sectionDefinition['description'] ?? $section->intro);
        $section->weight = (int) ($sectionDefinition['order'] ?? $section->weight);
        $section->extra = array_merge((array) ($section->extra ?? []), [
            'icon' => (string) ($sectionDefinition['icon'] ?? ''),
            'layout' => (array) data_get($sectionDefinition, 'schema.layout', ['mode' => 'block']),
            'schema' => \is_array($sectionDefinition['schema'] ?? null) ? $sectionDefinition['schema'] : ['fields' => []],
        ]);
        $section->status = 1;
        $section->save();

        $fieldMetaIndex = $this->buildFieldMetaIndex((array) data_get($sectionDefinition, 'schema.fields', []));
        $defaults = (array) ($sectionDefinition['defaults'] ?? []);
        foreach ($fieldMetaIndex as $name => $fieldMeta) {
            $value = array_key_exists($name, $defaults)
                ? $defaults[$name]
                : $this->resolveDefaultPluginFieldValue($fieldMeta)
            ;

            $this->syncSectionConfigItem($section, (string) $name, $value, $fieldMeta);
        }

        if ('overwrite' === $injectionStrategy) {
            SystemConfig::query()
                ->where('system_config_group_id', (int) $section->id)
                ->whereNotIn('name', array_keys($fieldMetaIndex))
                ->delete()
            ;
        }

        return $section->refresh();
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $sectionDefinition
     */
    public function shouldUseStoredSchema(string $code, array $settings, array $sectionDefinition): bool
    {
        $strategy = strtolower(trim((string) data_get($settings, 'injection.strategy', 'merge')));
        if ('skip' !== $strategy) {
            return false;
        }

        return SystemConfigGroup::query()
            ->where('addon_code', $code)
            ->where('name', sprintf('addon_%s_%s', $code, (string) ($sectionDefinition['key'] ?? 'basic')))
            ->exists()
        ;
    }

    /**
     * @param array<int, mixed> $fields
     *
     * @return array<string, array<string, mixed>>
     */
    private function buildFieldMetaIndex(array $fields): array
    {
        $fieldMetaIndex = [];
        foreach ($fields as $field) {
            if (!\is_array($field)) {
                continue;
            }

            $fieldName = trim((string) ($field['name'] ?? ''));
            if ('' === $fieldName) {
                continue;
            }

            $fieldMetaIndex[$fieldName] = $field;
        }

        return $fieldMetaIndex;
    }

    /**
     * @param mixed $value
     * @param array<string, mixed> $fieldMeta
     */
    private function syncSectionConfigItem(SystemConfigGroup $section, string $name, $value, array $fieldMeta = []): void
    {
        $name = trim($name);
        if ('' === $name) {
            return;
        }

        $type = $this->resolveConfigFieldType($value, $fieldMeta);
        $serialized = $this->serializeConfigValue($value, $type);

        /** @var SystemConfig $config */
        $config = SystemConfig::query()->firstOrNew([
            'system_config_group_id' => $section->id,
            'name' => $name,
        ]);

        $isNew = !$config->exists;
        $config->title = (string) ($fieldMeta['label'] ?? ucfirst(str_replace(['_', '-'], ' ', $name)));
        $config->type = $type;
        $config->intro = (string) ($fieldMeta['description'] ?? '');
        $config->extra = $this->normalizePluginConfigExtra($fieldMeta);
        $config->default_val = $serialized;
        $config->weight = (int) ($fieldMeta['order'] ?? 0);

        if ($isNew) {
            $config->value = $serialized;
        }

        $config->save();
    }

    /**
     * @param mixed $value
     * @param array<string, mixed> $fieldMeta
     */
    private function resolveConfigFieldType($value, array $fieldMeta = []): string
    {
        $type = strtolower(trim((string) ($fieldMeta['type'] ?? '')));
        if ('' !== $type) {
            if (ConfigRuleValidator::isValidFieldType($type)) {
                return $type;
            }

            return 'text';
        }

        $component = strtolower(trim((string) ($fieldMeta['component'] ?? '')));

        if (\in_array($component, ['switch'], true)) {
            return 'switch';
        }

        if (\in_array($component, ['textarea'], true)) {
            return 'textarea';
        }

        if (\in_array($component, ['radio'], true)) {
            return 'radio';
        }

        if (\in_array($component, ['checkbox'], true)) {
            return 'checkbox';
        }

        if (\in_array($component, ['select'], true)) {
            return 'select';
        }

        if (\is_bool($value)) {
            return 'switch';
        }

        if (\is_array($value)) {
            return 'json';
        }

        return 'text';
    }

    /**
     * @param array<string, mixed> $fieldMeta
     *
     * @return mixed
     */
    private function resolveDefaultPluginFieldValue(array $fieldMeta)
    {
        $type = $this->resolveConfigFieldType(null, $fieldMeta);

        if ('switch' === $type) {
            return false;
        }

        if (\in_array($type, ['checkbox', 'json'], true)) {
            return [];
        }

        return '';
    }

    /**
     * @param mixed $value
     */
    private function serializeConfigValue($value, string $type): string
    {
        if ('switch' === $type) {
            return (bool) $value ? '1' : '0';
        }

        if (\in_array($type, ['json', 'checkbox'], true)) {
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (null === $value) {
            return '';
        }

        return (string) $value;
    }

    /**
     * @param array<string, mixed> $fieldMeta
     *
     * @return array<string, mixed>
     */
    private function normalizePluginConfigExtra(array $fieldMeta): array
    {
        $meta = (array) ($fieldMeta['meta'] ?? []);
        $expose = strtolower(trim((string) ($meta['expose'] ?? 'private')));
        if (!ConfigRuleValidator::isValidExposeMode($expose)) {
            $expose = 'private';
        }
        $meta['expose'] = $expose;

        return [
            'options' => $this->normalizePluginFieldOptions($fieldMeta),
            'meta' => $meta,
        ];
    }

    /**
     * @param array<string, mixed> $fieldMeta
     *
     * @return array<string, string>
     */
    private function normalizePluginFieldOptions(array $fieldMeta): array
    {
        $options = (array) ($fieldMeta['options'] ?? []);
        $results = [];
        foreach ($options as $option) {
            if (!\is_array($option)) {
                continue;
            }

            $value = (string) ($option['value'] ?? '');
            if ('' === $value) {
                continue;
            }

            $results[$value] = (string) ($option['label'] ?? $value);
        }

        return $results;
    }
}
