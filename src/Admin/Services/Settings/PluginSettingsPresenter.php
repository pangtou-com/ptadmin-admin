<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Settings;

use PTAdmin\Admin\Support\ConfigRuleValidator;

class PluginSettingsPresenter
{
    /**
     * @param array<string, mixed> $addon
     * @param array<string, mixed> $settings
     *
     * @return array<string, mixed>
     */
    public function presentCatalogItem(string $code, array $addon, array $settings, bool $enabled): array
    {
        $mode = (string) ($settings['mode'] ?? 'hosted');

        return [
            'owner' => [
                'code' => $code,
                'name' => (string) ($addon['title'] ?? $addon['name'] ?? $code),
            ],
            'description' => (string) ($addon['description'] ?? $addon['intro'] ?? ''),
            'icon' => (string) ($settings['icon'] ?? ''),
            'enabled' => $enabled,
            'version' => (string) ($addon['version'] ?? ''),
            'settings' => [
                'enabled' => true,
                'mode' => $mode,
                'managed_by' => (string) ($settings['managed_by'] ?? ('external_route' === $mode ? 'plugin' : 'system')),
                'path' => 'external_route' === $mode ? (string) ($settings['path'] ?? '') : '',
                'injection' => (array) ($settings['injection'] ?? ['strategy' => 'merge']),
                'cleanup' => (array) ($settings['cleanup'] ?? ['on_uninstall' => 'retain']),
                'sections' => 'hosted' === $mode ? array_values(array_map(static function (array $section): array {
                    return [
                        'key' => (string) ($section['key'] ?? 'basic'),
                        'title' => (string) ($section['title'] ?? '基础配置'),
                        'description' => (string) ($section['description'] ?? ''),
                        'icon' => (string) ($section['icon'] ?? ''),
                        'order' => (int) ($section['order'] ?? 0),
                        'mode' => 'hosted',
                        'render' => [
                            'engine' => 'pt-render',
                            'version' => '1.0',
                        ],
                    ];
                }, (array) ($settings['sections'] ?? []))) : [],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $addon
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $sectionDefinition
     * @param array<string, mixed> $sectionPayload
     *
     * @return array<string, mixed>
     */
    public function presentHostedSection(string $code, array $addon, array $settings, array $sectionDefinition, array $sectionPayload, bool $useStoredSchema = false): array
    {
        $storedSchema = (array) data_get($sectionPayload, 'section.extra.schema', []);
        $schema = $useStoredSchema && [] !== $storedSchema
            ? $storedSchema
            : (!$useStoredSchema && \is_array($sectionDefinition['schema'] ?? null) && [] !== (array) ($sectionDefinition['schema'] ?? [])
            ? $sectionDefinition['schema']
            : ($sectionPayload['schema'] ?? ['fields' => []]));
        $managedBy = (string) ($settings['managed_by'] ?? 'system');
        $cleanup = (array) ($settings['cleanup'] ?? ['on_uninstall' => 'retain']);
        $storedSection = (array) ($sectionPayload['section'] ?? []);
        $sectionTitle = $useStoredSchema
            ? (string) ($storedSection['title'] ?? ($sectionDefinition['title'] ?? '基础配置'))
            : (string) ($sectionDefinition['title'] ?? '基础配置');
        $sectionDescription = $useStoredSchema
            ? (string) ($storedSection['intro'] ?? ($sectionDefinition['description'] ?? ''))
            : (string) ($sectionDefinition['description'] ?? '');
        $sectionExtra = array_merge((array) ($useStoredSchema ? ($storedSection['extra'] ?? []) : []), [
            'managed_by' => $managedBy,
            'cleanup' => $cleanup,
        ]);

        return [
            'scope' => 'plugin',
            'owner' => [
                'code' => $code,
                'name' => (string) ($addon['title'] ?? $addon['name'] ?? $code),
            ],
            'section' => [
                'key' => (string) ($sectionDefinition['key'] ?? 'basic'),
                'title' => $sectionTitle,
                'description' => $sectionDescription,
                'extra' => $sectionExtra,
            ],
            'render' => [
                'engine' => 'pt-render',
                'version' => '1.0',
                'schema' => $this->normalizeRenderSchema((array) $schema),
            ],
            'values' => (array) ($sectionPayload['values'] ?? []),
            'meta' => [
                'editable' => 'plugin' !== $managedBy,
                'supported' => true,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $addon
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function presentLegacySection(string $code, array $addon, string $sectionKey, array $payload): array
    {
        return [
            'scope' => 'plugin',
            'owner' => [
                'code' => $code,
                'name' => (string) ($addon['title'] ?? $addon['name'] ?? $code),
            ],
            'section' => [
                'key' => $sectionKey,
                'title' => (string) data_get($payload, 'section.title', '基础配置'),
                'description' => (string) data_get($payload, 'section.intro', '插件通用配置'),
            ],
            'render' => [
                'engine' => 'pt-render',
                'version' => '1.0',
                'schema' => $this->normalizeRenderSchema((array) ($payload['schema'] ?? ['fields' => []])),
            ],
            'values' => (array) ($payload['values'] ?? []),
            'meta' => [
                'editable' => (bool) ($payload['supported'] ?? true),
                'supported' => (bool) ($payload['supported'] ?? true),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    private function normalizeRenderSchema(array $schema): array
    {
        if (isset($schema['fields']) && \is_array($schema['fields'])) {
            $schema['fields'] = array_values(array_map(function ($field): array {
                return \is_array($field) ? $this->normalizeRenderField($field) : [];
            }, $schema['fields']));
        }

        if (isset($schema['children']) && \is_array($schema['children'])) {
            $schema['children'] = array_values(array_map(function ($field): array {
                return \is_array($field) ? $this->normalizeRenderField($field) : [];
            }, $schema['children']));
        }

        unset($schema['component']);

        return $schema;
    }

    /**
     * @param array<string, mixed> $field
     *
     * @return array<string, mixed>
     */
    private function normalizeRenderField(array $field): array
    {
        $type = strtolower(trim((string) ($field['type'] ?? '')));
        if ('' === $type) {
            $type = ConfigRuleValidator::resolveFieldType($field);
        }

        unset($field['component']);
        $field['type'] = '' !== $type ? $type : 'text';

        return $field;
    }
}
