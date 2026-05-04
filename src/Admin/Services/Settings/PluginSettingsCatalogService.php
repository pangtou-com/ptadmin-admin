<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Settings;

use PTAdmin\Addon\Addon;

class PluginSettingsCatalogService
{
    private PluginSettingsResolver $pluginSettingsResolver;
    private PluginSettingsPresenter $pluginSettingsPresenter;

    public function __construct(PluginSettingsResolver $pluginSettingsResolver, PluginSettingsPresenter $pluginSettingsPresenter)
    {
        $this->pluginSettingsResolver = $pluginSettingsResolver;
        $this->pluginSettingsPresenter = $pluginSettingsPresenter;
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $results = [];

        foreach ((array) Addon::getInstalledAddons() as $code => $addonInfo) {
            if (!\is_array($addonInfo)) {
                continue;
            }

            $settings = $this->pluginSettingsResolver->resolve((string) $code, $addonInfo);
            if ([] === $settings || !($settings['enabled'] ?? false)) {
                continue;
            }

            $mode = (string) ($settings['mode'] ?? 'hosted');
            if ('none' === $mode) {
                continue;
            }

            $results[] = $this->pluginSettingsPresenter->presentCatalogItem((string) $code, $addonInfo, $settings, Addon::hasAddon((string) $code));
        }

        usort($results, static function (array $left, array $right): int {
            $leftOrder = (int) ($left['settings']['sections'][0]['order'] ?? PHP_INT_MAX);
            $rightOrder = (int) ($right['settings']['sections'][0]['order'] ?? PHP_INT_MAX);

            if ($leftOrder !== $rightOrder) {
                return $leftOrder <=> $rightOrder;
            }

            return strcmp((string) ($left['owner']['code'] ?? ''), (string) ($right['owner']['code'] ?? ''));
        });

        return [
            'scope' => 'plugin',
            'results' => array_values($results),
        ];
    }
}
