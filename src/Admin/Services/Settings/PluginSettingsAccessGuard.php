<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Settings;

use PTAdmin\Foundation\Exceptions\BackgroundException;

class PluginSettingsAccessGuard
{
    /**
     * @param array<string, mixed> $settings
     */
    public function assertHostedSectionReadable(string $code, array $settings): void
    {
        $mode = (string) ($settings['mode'] ?? 'hosted');

        if ('none' === $mode) {
            throw new BackgroundException(sprintf('插件[%s]当前使用 none 模式，不提供 settings section', $code));
        }

        if ('external_route' === $mode) {
            throw new BackgroundException(sprintf('插件[%s]当前使用 external_route 模式，不提供 hosted settings section', $code));
        }
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function assertHostedSectionWritable(string $code, array $settings): void
    {
        $managedBy = (string) ($settings['managed_by'] ?? 'system');
        if ('plugin' === $managedBy && 'external_route' !== (string) ($settings['mode'] ?? 'hosted')) {
            throw new BackgroundException(sprintf('插件[%s]当前由插件自身管理配置，不允许通过系统设置中心保存', $code));
        }
    }

    public function assertLegacySectionKey(string $code, string $sectionKey): void
    {
        if ('basic' === $sectionKey) {
            return;
        }

        throw new BackgroundException(sprintf('插件[%s]的 legacy 配置仅支持[basic]分组，当前请求[%s]', $code, $sectionKey));
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @return array<string, mixed>
     */
    public function resolveSectionDefinition(string $code, array $settings, string $sectionKey): array
    {
        foreach ((array) ($settings['sections'] ?? []) as $section) {
            if (!\is_array($section)) {
                continue;
            }

            if ((string) ($section['key'] ?? '') === $sectionKey) {
                return $section;
            }
        }

        throw new BackgroundException(sprintf('插件[%s]的设置分组[%s]不存在', $code, $sectionKey));
    }
}
