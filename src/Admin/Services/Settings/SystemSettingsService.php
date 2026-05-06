<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Settings;

use PTAdmin\Admin\Models\SystemConfigGroup;
use PTAdmin\Admin\Services\SystemConfigService;
use PTAdmin\Foundation\Auth\AdminAuth;
use PTAdmin\Foundation\Exceptions\BackgroundException;

class SystemSettingsService
{
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @return array<string, mixed>
     */
    public function catalog(): array
    {
        /** @var SystemConfigGroup|null $systemRoot */
        $systemRoot = SystemConfigGroup::query()
            ->whereNull('addon_code')
            ->where('parent_id', 0)
            ->where('name', 'system')
            ->where('status', 1)
            ->first();

        $sections = [];
        /** @var \Illuminate\Support\Collection<int, SystemConfigGroup> $roots */
        $roots = SystemConfigGroup::query()
            ->whereNull('addon_code')
            ->where('parent_id', 0)
            ->where('status', 1)
            ->orderBy('weight', 'desc')
            ->orderBy('id')
            ->get();

        /** @var SystemConfigGroup $root */
        foreach ($roots as $root) {
            /** @var \Illuminate\Support\Collection<int, SystemConfigGroup> $children */
            $children = SystemConfigGroup::query()
                ->whereNull('addon_code')
                ->where('parent_id', (int) $root->id)
                ->where('status', 1)
                ->orderBy('weight', 'desc')
                ->orderBy('id')
                ->get();

            /** @var SystemConfigGroup $section */
            foreach ($children as $section) {
                $sections[] = $this->formatCatalogSectionItem($root, $section);
            }
        }

        return [
            'scope' => 'system',
            'owner' => [
                'code' => 'system',
                'name' => null !== $systemRoot ? (string) $systemRoot->title : '系统设置',
            ],
            'sections' => $sections,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function section(string $sectionKey): array
    {
        $section = $this->resolveSection($sectionKey);
        $payload = $this->systemConfigService->section((int) $section->id);

        return $this->formatSectionPayload($payload);
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public function saveSection(string $sectionKey, array $input): array
    {
        $section = $this->resolveSection($sectionKey);
        $values = $this->systemConfigService->saveSection((int) $section->id, $input);

        return [
            'values' => $values,
            'meta' => $this->buildUpdateMeta(),
        ];
    }
    
    private function resolveSection(string $sectionKey): SystemConfigGroup
    {
        [$rootName, $resolvedSectionKey] = $this->parseSectionKey($sectionKey);

        /** @var SystemConfigGroup|null $root */
        $root = SystemConfigGroup::query()
            ->whereNull('addon_code')
            ->where('parent_id', 0)
            ->where('name', $rootName)
            ->where('status', 1)
            ->first();

        if (null === $root) {
            throw new BackgroundException(sprintf('系统设置根分组[%s]不存在', $rootName));
        }

        /** @var SystemConfigGroup|null $section */
        $section = SystemConfigGroup::query()
            ->whereNull('addon_code')
            ->where('parent_id', (int) $root->id)
            ->where('name', $resolvedSectionKey)
            ->where('status', 1)
            ->first();

        if (null === $section) {
            throw new BackgroundException(sprintf('系统设置分组[%s]不存在', $sectionKey));
        }

        return $section;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function formatSectionPayload(array $payload): array
    {
        $group = (array) ($payload['group'] ?? []);
        $section = (array) ($payload['section'] ?? []);
        $groupName = (string) ($group['name'] ?? 'system');
        $sectionName = (string) ($section['name'] ?? '');
        $sectionTitle = (string) ($section['title'] ?? '');

        return [
            'scope' => 'system',
            'owner' => [
                'code' => 'system',
                'name' => '系统设置',
            ],
            'section' => [
                'key' => $this->buildSectionKey($groupName, $sectionName),
                'title' => 'system' === $groupName ? $sectionTitle : sprintf('%s / %s', (string) ($group['title'] ?? $groupName), $sectionTitle),
                'description' => (string) ($section['intro'] ?? ''),
                'extra' => (array) ($section['extra'] ?? []),
            ],
            'render' => [
                'engine' => 'pt-render',
                'version' => '1.0',
                'schema' => $payload['schema'] ?? ['fields' => []],
            ],
            'values' => (array) ($payload['values'] ?? []),
            'meta' => [
                'editable' => true,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildUpdateMeta(): array
    {
        $user = AdminAuth::user();

        return [
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => null !== $user ? (string) ($user->nickname ?: $user->username) : '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCatalogSectionItem(SystemConfigGroup $root, SystemConfigGroup $section): array
    {
        $title = 'system' === (string) $root->name
            ? (string) $section->title
            : sprintf('%s / %s', (string) $root->title, (string) $section->title);

        return [
            'key' => $this->buildSectionKey((string) $root->name, (string) $section->name),
            'title' => $title,
            'description' => (string) ($section->intro ?: $root->intro ?: ''),
            'icon' => (string) data_get($section->extra, 'icon', data_get($root->extra, 'icon', '')),
            'order' => (int) $section->weight,
            'mode' => 'hosted',
            'render' => [
                'engine' => 'pt-render',
                'version' => '1.0',
            ],
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseSectionKey(string $sectionKey): array
    {
        $normalized = trim($sectionKey);
        if ('' === $normalized) {
            throw new BackgroundException('系统设置分组标识不能为空');
        }

        if (false === strpos($normalized, '.')) {
            return ['system', $normalized];
        }

        [$rootName, $childName] = array_pad(explode('.', $normalized, 2), 2, '');
        $rootName = trim($rootName);
        $childName = trim($childName);

        if ('' === $rootName || '' === $childName) {
            throw new BackgroundException(sprintf('系统设置分组[%s]格式不正确', $sectionKey));
        }

        return [$rootName, $childName];
    }

    private function buildSectionKey(string $rootName, string $sectionName): string
    {
        if ('system' === $rootName) {
            return $sectionName;
        }

        return sprintf('%s.%s', $rootName, $sectionName);
    }
}
