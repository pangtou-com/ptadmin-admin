<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services;

use PTAdmin\Admin\Models\SystemConfigGroup;
use PTAdmin\Foundation\Exceptions\BackgroundException;

class SystemSettingsService
{
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function catalog(): \Illuminate\Support\Collection
    {
        /** @var \Illuminate\Support\Collection<int, SystemConfigGroup> $roots */
        $roots = SystemConfigGroup::query()
            ->where('status', 1)
            ->whereNull('addon_code')
            ->orderBy('type')
            ->orderByDesc('sort')
            ->orderBy('id')->get();
    
        $settingTypes = collect(config('ptadmin.setting_type', []));
        $group = array_to_map($settingTypes->all(), 'value', 'label');
        $sortMap = array_to_map($settingTypes->all(), 'value', 'sort');

        return $roots
            ->groupBy(static function (SystemConfigGroup $group): string {
                return (string) $group->type;
            })->map(function (\Illuminate\Support\Collection $items, string $type) use ($group, $sortMap): array {
                return [
                    'type' => $type,
                    'title' => $group[$type] ?? "未定义",
                    'sort' => isset($sortMap[$type]) && is_numeric($sortMap[$type]) ? (int) $sortMap[$type] : PHP_INT_MAX,
                    'items' => $items->map(function (SystemConfigGroup $group): array {
                        return $group->toArray();
                    })->values()->all(),
                ];
            })->sortBy(static function (array $item): array {
                return [(int) ($item['sort'] ?? PHP_INT_MAX), (string) ($item['type'] ?? '')];
            })->values()->map(static function (array $item): array {
                unset($item['sort']);

                return $item;
            });
    }
    

    /**
     * 根据分组name获取分组的配置信息
     * @return array<string, mixed>
     */
    public function section(string $sectionKey): array
    {
        $group = $this->resolveSection($sectionKey);
        
        return $this->systemConfigService->section((int) $group->id);
    }

    /**
     * @return array<string, mixed>
     */
    public function addonSection(string $addonCode, string $sectionKey): array
    {
        $group = $this->resolveSection($sectionKey, $addonCode);

        return $this->systemConfigService->section((int) $group->id);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function saveSection(string $sectionKey, array $input)
    {
        $section = $this->resolveSection($sectionKey);

        $this->systemConfigService->saveSection($section->id, $input);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function saveAddonSection(string $addonCode, string $sectionKey, array $input)
    {
        $section = $this->resolveSection($sectionKey, $addonCode);

        $this->systemConfigService->saveSection($section->id, $input);
    }
    
    /**
     * 根据key 解析出场景表单信息
     * @param string $sectionKey
     * @param string|null $addonCode
     * @return SystemConfigGroup
     */
    private function resolveSection(string $sectionKey, ?string $addonCode = null): SystemConfigGroup
    {
        /** @var SystemConfigGroup|null $root */
        $root = SystemConfigGroup::query()
            ->where('name', $sectionKey)
            ->where('status', 1)
            ->when(null === $addonCode, static function ($query): void {
                $query->whereNull('addon_code');
            })
            ->when(null !== $addonCode, static function ($query) use ($addonCode): void {
                $query->where('addon_code', $addonCode);
            })
            ->first();
        if (null === $root) {
            if (null !== $addonCode) {
                throw new BackgroundException(sprintf('插件[%s]配置分组[%s]不存在', $addonCode, $sectionKey));
            }

            throw new BackgroundException(sprintf('系统设置分组[%s]不存在', $sectionKey));
        }
        
        return $root;
    }
    
    
}
