<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Settings;

class SettingsRegistryService
{
    private PluginSettingsResolver $pluginSettingsResolver;
    private PluginSettingsAddonLocator $pluginSettingsAddonLocator;
    private PluginSettingsCleanupService $pluginSettingsCleanupService;
    private PluginSettingsCatalogService $pluginSettingsCatalogService;
    private PluginSettingsSectionService $pluginSettingsSectionService;

    public function __construct(
        PluginSettingsResolver $pluginSettingsResolver,
        PluginSettingsAddonLocator $pluginSettingsAddonLocator,
        PluginSettingsCleanupService $pluginSettingsCleanupService,
        PluginSettingsCatalogService $pluginSettingsCatalogService,
        PluginSettingsSectionService $pluginSettingsSectionService
    )
    {
        $this->pluginSettingsResolver = $pluginSettingsResolver;
        $this->pluginSettingsAddonLocator = $pluginSettingsAddonLocator;
        $this->pluginSettingsCleanupService = $pluginSettingsCleanupService;
        $this->pluginSettingsCatalogService = $pluginSettingsCatalogService;
        $this->pluginSettingsSectionService = $pluginSettingsSectionService;
    }

    /**
     * @return array<string, mixed>
     */
    public function pluginCatalog(): array
    {
        return $this->pluginSettingsCatalogService->build();
    }

    /**
     * @return array<string, mixed>
     */
    public function pluginSection(string $code, string $sectionKey): array
    {
        return $this->pluginSettingsSectionService->detail($code, $sectionKey);
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public function savePluginSection(string $code, string $sectionKey, array $input): array
    {
        return $this->pluginSettingsSectionService->save($code, $sectionKey, $input);
    }

    /**
     * @return array<string, mixed>
     */
    public function pluginSettingsRegistration(string $code): array
    {
        $addon = $this->pluginSettingsAddonLocator->resolveInstalledAddon($code);

        return $this->pluginSettingsResolver->resolve($code, $addon);
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function uninstallPluginSettings(string $code, array $settings): void
    {
        $this->pluginSettingsCleanupService->uninstallPluginSettings($code, $settings);
    }

}
