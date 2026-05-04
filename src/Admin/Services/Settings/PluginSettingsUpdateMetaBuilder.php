<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Settings;

use PTAdmin\Foundation\Auth\AdminAuth;

class PluginSettingsUpdateMetaBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $user = AdminAuth::user();

        return [
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => null !== $user ? (string) ($user->nickname ?: $user->username) : '',
        ];
    }
}
