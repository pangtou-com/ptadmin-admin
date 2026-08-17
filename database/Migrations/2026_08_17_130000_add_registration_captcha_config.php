<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use PTAdmin\Admin\Models\SystemConfig;
use PTAdmin\Admin\Models\SystemConfigGroup;

return new class extends Migration
{
    public function up(): void
    {
        $group = SystemConfigGroup::query()
            ->whereNull('addon_code')
            ->where('name', 'security')
            ->first();
        if (null === $group) {
            return;
        }

        foreach ($this->fields() as $field) {
            SystemConfig::query()->firstOrCreate(
                [
                    'system_config_group_id' => $group->id,
                    'name' => $field['name'],
                ],
                $field + [
                    'system_config_group_id' => $group->id,
                    'is_system' => 1,
                ]
            );
        }
    }

    public function down(): void
    {
        $group = SystemConfigGroup::query()
            ->whereNull('addon_code')
            ->where('name', 'security')
            ->first();
        if (null === $group) {
            return;
        }

        SystemConfig::query()
            ->where('system_config_group_id', $group->id)
            ->whereIn('name', ['register_captcha', 'register_captcha_provider'])
            ->delete();
    }

    /** @return array<int, array<string, mixed>> */
    private function fields(): array
    {
        return [
            [
                'title' => '注册验证码',
                'name' => 'register_captcha',
                'type' => 'switch',
                'value' => 0,
                'default_val' => 0,
                'sort' => 70,
                'intro' => '控制前台用户注册时是否启用验证码校验',
            ],
            [
                'title' => '注册挑战提供者',
                'name' => 'register_captcha_provider',
                'type' => 'text',
                'value' => '',
                'default_val' => '',
                'sort' => 60,
                'intro' => '填写注册场景的插件挑战能力引用，格式为 addon_code:capability_code',
            ],
        ];
    }
};
