<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Settings;

use PTAdmin\Admin\Services\AddonPlatformService;
use PTAdmin\Admin\Services\SystemConfigService;

class PluginSettingsSectionService
{
    private AddonPlatformService $addonPlatformService;
    private PluginSettingsInjector $pluginSettingsInjector;
    private PluginSettingsResolver $pluginSettingsResolver;
    private PluginSettingsPresenter $pluginSettingsPresenter;
    private PluginSettingsAccessGuard $pluginSettingsAccessGuard;
    private PluginSettingsAddonLocator $pluginSettingsAddonLocator;
    private PluginSettingsUpdateMetaBuilder $pluginSettingsUpdateMetaBuilder;
    private SystemConfigService $systemConfigService;

    public function __construct(
        AddonPlatformService $addonPlatformService,
        PluginSettingsInjector $pluginSettingsInjector,
        PluginSettingsResolver $pluginSettingsResolver,
        PluginSettingsPresenter $pluginSettingsPresenter,
        PluginSettingsAccessGuard $pluginSettingsAccessGuard,
        PluginSettingsAddonLocator $pluginSettingsAddonLocator,
        PluginSettingsUpdateMetaBuilder $pluginSettingsUpdateMetaBuilder,
        SystemConfigService $systemConfigService
    ) {
        $this->addonPlatformService = $addonPlatformService;
        $this->pluginSettingsInjector = $pluginSettingsInjector;
        $this->pluginSettingsResolver = $pluginSettingsResolver;
        $this->pluginSettingsPresenter = $pluginSettingsPresenter;
        $this->pluginSettingsAccessGuard = $pluginSettingsAccessGuard;
        $this->pluginSettingsAddonLocator = $pluginSettingsAddonLocator;
        $this->pluginSettingsUpdateMetaBuilder = $pluginSettingsUpdateMetaBuilder;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(string $code, string $sectionKey): array
    {
        $addon = $this->pluginSettingsAddonLocator->resolveInstalledAddon($code);
        $settings = $this->pluginSettingsResolver->resolve($code, $addon);
        $this->pluginSettingsAccessGuard->assertHostedSectionReadable($code, $settings);

        if ('legacy' === (string) ($settings['source'] ?? '') || [] === $settings || [] === (array) ($settings['sections'] ?? [])) {
            $this->pluginSettingsAccessGuard->assertLegacySectionKey($code, $sectionKey);
            $payload = $this->addonPlatformService->addonConfig($code);

            return $this->pluginSettingsPresenter->presentLegacySection($code, $addon, $sectionKey, $payload);
        }

        $sectionDefinition = $this->pluginSettingsAccessGuard->resolveSectionDefinition($code, $settings, $sectionKey);
        $useStoredSchema = $this->pluginSettingsInjector->shouldUseStoredSchema($code, $settings, $sectionDefinition);
        $group = $this->pluginSettingsInjector->ensureSection($code, $addon, $settings, $sectionDefinition);
        $sectionPayload = $this->systemConfigService->section((int) $group->id);

        return $this->pluginSettingsPresenter->presentHostedSection($code, $addon, $settings, $sectionDefinition, $sectionPayload, $useStoredSchema);
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public function save(string $code, string $sectionKey, array $input): array
    {
        $addon = $this->pluginSettingsAddonLocator->resolveInstalledAddon($code);
        $settings = $this->pluginSettingsResolver->resolve($code, $addon);
        $this->pluginSettingsAccessGuard->assertHostedSectionReadable($code, $settings);

        if ('legacy' === (string) ($settings['source'] ?? '') || [] === $settings || [] === (array) ($settings['sections'] ?? [])) {
            $this->pluginSettingsAccessGuard->assertLegacySectionKey($code, $sectionKey);
            $payload = $this->addonPlatformService->saveAddonConfig($code, $input);

            return [
                'values' => (array) ($payload['values'] ?? []),
                'meta' => $this->pluginSettingsUpdateMetaBuilder->build(),
            ];
        }

        $sectionDefinition = $this->pluginSettingsAccessGuard->resolveSectionDefinition($code, $settings, $sectionKey);
        $this->pluginSettingsAccessGuard->assertHostedSectionWritable($code, $settings);
        $group = $this->pluginSettingsInjector->ensureSection($code, $addon, $settings, $sectionDefinition);
        $values = $this->systemConfigService->saveSection((int) $group->id, $input);

        return [
            'values' => $values,
            'meta' => $this->pluginSettingsUpdateMetaBuilder->build(),
        ];
    }
}
