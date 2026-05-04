<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Settings;

use PTAdmin\Admin\Models\SystemConfigGroup;
use PTAdmin\Admin\Support\ConfigRuleValidator;
use PTAdmin\Foundation\Exceptions\BackgroundException;

class PluginSettingsResolver
{
    /**
     * @param array<string, mixed> $addon
     *
     * @return array<string, mixed>
     */
    public function resolve(string $code, array $addon): array
    {
        $registrationPath = addon_path($code, 'Config/settings.php');
        if (is_file($registrationPath)) {
            $payload = require $registrationPath;
            if (!\is_array($payload)) {
                throw new BackgroundException(__('ptadmin::background.plugin_settings_payload_invalid', ['code' => $code]));
            }

            $this->assertRegistrationAllowed($code, $payload);
            $mode = strtolower(trim((string) ($payload['mode'] ?? 'hosted')));
            $sections = array_values(array_filter((array) ($payload['sections'] ?? []), static function ($item): bool {
                return \is_array($item);
            }));

            return [
                'enabled' => (bool) ($payload['enabled'] ?? true),
                'mode' => $mode,
                'path' => (string) ($payload['path'] ?? ''),
                'icon' => (string) ($payload['icon'] ?? ''),
                'managed_by' => $this->normalizeManagedBy($payload['managed_by'] ?? null, $mode),
                'injection' => $this->normalizeInjection((array) ($payload['injection'] ?? [])),
                'cleanup' => $this->normalizeCleanup((array) ($payload['cleanup'] ?? [])),
                'source' => 'registration',
                'sections' => array_map(static function (array $section): array {
                    return [
                        'key' => (string) ($section['key'] ?? 'basic'),
                        'title' => (string) ($section['title'] ?? '基础配置'),
                        'description' => (string) ($section['description'] ?? ''),
                        'icon' => (string) ($section['icon'] ?? ''),
                        'order' => (int) ($section['order'] ?? 0),
                        'schema' => \is_array($section['schema'] ?? null) ? $section['schema'] : ['fields' => []],
                        'defaults' => \is_array($section['defaults'] ?? null) ? $section['defaults'] : [],
                    ];
                }, $sections),
            ];
        }

        $legacyConfigPath = addon_path($code, 'Config/config.php');
        if (is_file($legacyConfigPath) || $this->hasLegacyPluginSection($code)) {
            return [
                'enabled' => true,
                'mode' => 'hosted',
                'managed_by' => 'plugin',
                'injection' => [
                    'strategy' => 'merge',
                ],
                'cleanup' => [
                    'on_uninstall' => 'retain',
                ],
                'path' => '',
                'icon' => '',
                'source' => 'legacy',
                'sections' => [
                    [
                        'key' => 'basic',
                        'title' => '基础配置',
                        'description' => '插件通用配置',
                        'icon' => '',
                        'order' => 10,
                        'schema' => ['fields' => []],
                        'defaults' => $this->loadLegacyPluginDefaults($legacyConfigPath),
                    ],
                ],
            ];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function normalizeInjection(array $payload): array
    {
        $strategy = strtolower(trim((string) ($payload['strategy'] ?? 'merge')));
        if (!\in_array($strategy, ['merge', 'overwrite', 'skip'], true)) {
            $strategy = 'merge';
        }

        return [
            'strategy' => $strategy,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function normalizeCleanup(array $payload): array
    {
        $strategy = strtolower(trim((string) ($payload['on_uninstall'] ?? 'retain')));
        if (!\in_array($strategy, ['retain', 'purge'], true)) {
            $strategy = 'retain';
        }

        return [
            'on_uninstall' => $strategy,
        ];
    }

    /**
     * @param mixed $managedBy
     */
    private function normalizeManagedBy($managedBy, string $mode): string
    {
        $resolved = strtolower(trim((string) $managedBy));
        if (\in_array($resolved, ['system', 'plugin'], true)) {
            return $resolved;
        }

        return 'external_route' === $mode ? 'plugin' : 'system';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function assertRegistrationAllowed(string $code, array $payload): void
    {
        $mode = strtolower(trim((string) ($payload['mode'] ?? 'hosted')));
        if (!\in_array($mode, ['hosted', 'external_route', 'none'], true)) {
            throw new BackgroundException(__('ptadmin::background.plugin_settings_mode_invalid', ['code' => $code]));
        }

        if (array_key_exists('managed_by', $payload)) {
            $managedBy = strtolower(trim((string) $payload['managed_by']));
            if (!\in_array($managedBy, ['system', 'plugin'], true)) {
                throw new BackgroundException(__('ptadmin::background.plugin_settings_managed_by_invalid', ['code' => $code]));
            }
        }

        if (array_key_exists('injection', $payload)) {
            $strategy = strtolower(trim((string) data_get($payload, 'injection.strategy', '')));
            if (!\in_array($strategy, ['merge', 'overwrite', 'skip'], true)) {
                throw new BackgroundException(__('ptadmin::background.plugin_settings_injection_invalid', ['code' => $code]));
            }
        }

        if (array_key_exists('cleanup', $payload)) {
            $strategy = strtolower(trim((string) data_get($payload, 'cleanup.on_uninstall', '')));
            if (!\in_array($strategy, ['retain', 'purge'], true)) {
                throw new BackgroundException(__('ptadmin::background.plugin_settings_cleanup_invalid', ['code' => $code]));
            }
        }

        if ('none' === $mode) {
            return;
        }

        if ('external_route' === $mode) {
            if ('' === trim((string) ($payload['path'] ?? ''))) {
                throw new BackgroundException(__('ptadmin::background.plugin_settings_external_route_path_required', ['code' => $code]));
            }

            return;
        }

        $sections = (array) ($payload['sections'] ?? []);
        if ([] === array_values(array_filter($sections, static function ($item): bool {
            return \is_array($item);
        }))) {
            throw new BackgroundException(__('ptadmin::background.plugin_settings_hosted_sections_required', ['code' => $code]));
        }

        $sectionKeys = [];
        foreach ($sections as $section) {
            if (!\is_array($section)) {
                continue;
            }

            $sectionKey = trim((string) ($section['key'] ?? ''));
            if ('' === $sectionKey) {
                throw new BackgroundException(__('ptadmin::background.plugin_settings_section_key_required', ['code' => $code]));
            }

            if (isset($sectionKeys[$sectionKey])) {
                throw new BackgroundException(__('ptadmin::background.plugin_settings_section_key_duplicate', ['code' => $code, 'section' => $sectionKey]));
            }
            $sectionKeys[$sectionKey] = true;

            if ('' === trim((string) ($section['title'] ?? ''))) {
                throw new BackgroundException(__('ptadmin::background.plugin_settings_section_title_required', ['code' => $code, 'section' => $sectionKey]));
            }

            $schema = $section['schema'] ?? null;
            if (!\is_array($schema)) {
                throw new BackgroundException(__('ptadmin::background.plugin_settings_section_schema_invalid', ['code' => $code, 'section' => $sectionKey]));
            }

            $defaults = $section['defaults'] ?? null;
            if (!\is_array($defaults)) {
                throw new BackgroundException(__('ptadmin::background.plugin_settings_section_defaults_invalid', ['code' => $code, 'section' => $sectionKey]));
            }

            $layout = (array) ($schema['layout'] ?? []);
            if ([] !== $layout) {
                $layoutMode = strtolower(trim((string) ($layout['mode'] ?? 'block')));
                if (!ConfigRuleValidator::isValidGroupLayoutMode($layoutMode)) {
                    throw new BackgroundException(__('ptadmin::background.config_group_layout_invalid'));
                }
            }

            $fields = (array) ($schema['fields'] ?? []);
            if ([] === $fields) {
                throw new BackgroundException(__('ptadmin::background.plugin_settings_section_fields_required', ['code' => $code, 'section' => $sectionKey]));
            }

            $fieldNames = [];
            foreach ($fields as $field) {
                if (!\is_array($field)) {
                    continue;
                }

                $fieldName = trim((string) ($field['name'] ?? ''));
                if ('' === $fieldName) {
                    throw new BackgroundException(__('ptadmin::background.plugin_settings_field_name_required', ['code' => $code, 'section' => $sectionKey]));
                }

                if (isset($fieldNames[$fieldName])) {
                    throw new BackgroundException(__('ptadmin::background.plugin_settings_field_name_duplicate', ['code' => $code, 'section' => $sectionKey, 'field' => $fieldName]));
                }
                $fieldNames[$fieldName] = true;

                if ('' === trim((string) ($field['label'] ?? ''))) {
                    throw new BackgroundException(__('ptadmin::background.plugin_settings_field_label_required', ['code' => $code, 'section' => $sectionKey, 'field' => $fieldName]));
                }

                $fieldType = ConfigRuleValidator::resolveFieldType($field);
                if (!ConfigRuleValidator::isValidFieldType($fieldType)) {
                    throw new BackgroundException(__('ptadmin::background.plugin_settings_field_type_invalid', ['code' => $code, 'section' => $sectionKey, 'field' => $fieldName]));
                }

                $meta = (array) ($field['meta'] ?? []);
                foreach (array_keys($meta) as $key) {
                    if (!\is_string($key)) {
                        continue;
                    }

                    $resolvedKey = strtolower(trim($key));
                    if ('' === $resolvedKey) {
                        continue;
                    }

                    if (!\in_array($resolvedKey, ConfigRuleValidator::supportedFieldMetaKeys(), true)) {
                        throw new BackgroundException(__('ptadmin::background.plugin_settings_field_meta_key_invalid', [
                            'code' => $code,
                            'section' => $sectionKey,
                            'field' => $fieldName,
                            'key' => $resolvedKey,
                        ]));
                    }
                }

                if ([] !== $meta && array_key_exists('expose', $meta) && !ConfigRuleValidator::isValidExposeMode((string) $meta['expose'])) {
                    throw new BackgroundException(__('ptadmin::background.plugin_settings_field_expose_invalid', ['code' => $code, 'section' => $sectionKey, 'field' => $fieldName]));
                }

                $unsupportedKeys = ConfigRuleValidator::unsupportedMetaKeysForFieldType($fieldType, $meta);
                if ([] !== $unsupportedKeys) {
                    throw new BackgroundException(__('ptadmin::background.plugin_settings_field_meta_unsupported', [
                        'code' => $code,
                        'section' => $sectionKey,
                        'field' => $fieldName,
                        'type' => $fieldType,
                        'key' => $unsupportedKeys[0],
                    ]));
                }

                $invalidMeta = ConfigRuleValidator::invalidMetaValueTypes($meta);
                if ([] !== $invalidMeta) {
                    throw new BackgroundException(__('ptadmin::background.plugin_settings_field_meta_value_invalid', [
                        'code' => $code,
                        'section' => $sectionKey,
                        'field' => $fieldName,
                        'key' => reset($invalidMeta),
                    ]));
                }
            }

            foreach ($defaults as $fieldName => $value) {
                unset($value);

                if (isset($fieldNames[(string) $fieldName])) {
                    continue;
                }

                throw new BackgroundException(__('ptadmin::background.plugin_settings_default_field_missing', ['code' => $code, 'section' => $sectionKey, 'field' => (string) $fieldName]));
            }
        }
    }

    private function hasLegacyPluginSection(string $code): bool
    {
        return SystemConfigGroup::query()
            ->where('addon_code', $code)
            ->where('name', 'basic')
            ->exists()
        ;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadLegacyPluginDefaults(string $legacyConfigPath): array
    {
        if (!is_file($legacyConfigPath)) {
            return [];
        }

        $payload = require $legacyConfigPath;

        return \is_array($payload) ? $payload : [];
    }
}
