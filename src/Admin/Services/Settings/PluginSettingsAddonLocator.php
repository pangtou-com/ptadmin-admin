<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Settings;

use PTAdmin\Addon\Addon;
use PTAdmin\Foundation\Exceptions\BackgroundException;

class PluginSettingsAddonLocator
{
    /**
     * @return array<string, mixed>
     */
    public function resolveInstalledAddon(string $code): array
    {
        $installed = (array) Addon::getInstalledAddons();

        if (!isset($installed[$code]) || !\is_array($installed[$code])) {
            throw new BackgroundException(sprintf('插件[%s]不存在', $code));
        }

        return $installed[$code];
    }
}
