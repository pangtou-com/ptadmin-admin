<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Settings;

use PTAdmin\Admin\Models\SystemConfig;
use PTAdmin\Admin\Models\SystemConfigGroup;

class PluginSettingsCleanupService
{
    /**
     * @param array<string, mixed> $settings
     */
    public function uninstallPluginSettings(string $code, array $settings): void
    {
        $cleanup = (array) ($settings['cleanup'] ?? []);
        $strategy = strtolower(trim((string) ($cleanup['on_uninstall'] ?? 'retain')));
        if ('purge' !== $strategy) {
            return;
        }

        $groups = SystemConfigGroup::query()->where('addon_code', $code)->get();
        foreach ($groups as $group) {
            SystemConfig::query()->where('system_config_group_id', (int) $group->id)->delete();
        }

        SystemConfigGroup::query()->where('addon_code', $code)->delete();
    }
}
