<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Database;

use PTAdmin\Admin\Models\SystemConfig;
use PTAdmin\Admin\Models\SystemConfigGroup;
use PTAdmin\Admin\Services\SystemConfigGroupService;
use PTAdmin\Admin\Services\SystemConfigService;
use PTAdmin\Admin\Support\SystemConfigPreset;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminSystemConfigInitializationTest extends TestCase
{
    public function test_install_initialize_is_idempotent_and_updates_existing_records(): void
    {
        $this->createSystemConfigGroupsTable();
        $this->createSystemConfigsTable();

        $initial = [
            [
                'title' => '基础设置',
                'name' => 'basic',
                'type' => 'system',
                'access' => 'public',
                'sort' => 100,
                'status' => 1,
                'fields' => [
                    [
                        'title' => '站点标题',
                        'name' => 'site_title',
                        'type' => 'text',
                        'value' => 'PTAdmin',
                        'default_val' => 'PTAdmin',
                        'sort' => 100,
                    ],
                ],
            ],
        ];

        $updated = [
            [
                'title' => '基础设置更新',
                'name' => 'basic',
                'type' => 'system',
                'access' => 'public',
                'sort' => 120,
                'status' => 1,
                'fields' => [
                    [
                        'title' => '站点标题更新',
                        'name' => 'site_title',
                        'type' => 'text',
                        'value' => 'PTAdmin Next',
                        'default_val' => 'PTAdmin Next',
                        'sort' => 110,
                    ],
                ],
            ],
        ];

        SystemConfigGroupService::installInitialize($initial);
        SystemConfigGroupService::installInitialize($updated);

        self::assertSame(1, SystemConfigGroup::query()->count());
        self::assertSame(1, SystemConfig::query()->count());
        self::assertSame('基础设置更新', SystemConfigGroup::query()->where('name', 'basic')->value('title'));
        self::assertSame(120, (int) SystemConfigGroup::query()->where('name', 'basic')->value('sort'));
        self::assertSame('站点标题更新', SystemConfig::query()->where('name', 'site_title')->value('title'));
        self::assertSame('PTAdmin Next', SystemConfig::query()->where('name', 'site_title')->value('value'));
    }

    public function test_install_initialize_uses_group_name_plus_field_name_for_system_values(): void
    {
        $this->createSystemConfigGroupsTable();
        $this->createSystemConfigsTable();

        SystemConfigGroupService::installInitialize([
            [
                'title' => '基础设置',
                'name' => 'basic',
                'type' => 'system',
                'access' => 'public',
                'sort' => 100,
                'status' => 1,
                'fields' => [
                    [
                        'title' => '站点标题',
                        'name' => 'site_title',
                        'type' => 'text',
                        'value' => 'PTAdmin',
                        'default_val' => 'PTAdmin',
                        'sort' => 100,
                    ],
                ],
            ],
        ]);

        SystemConfigService::updateSystemConfigCache();

        self::assertSame('PTAdmin', SystemConfigService::value('basic.site_title'));
        self::assertSame('PTAdmin', SystemConfigService::public()['basic.site_title'] ?? null);
    }

    public function test_install_initialize_uses_addon_namespace_plus_group_name_plus_field_name_for_addon_values(): void
    {
        $this->createSystemConfigGroupsTable();
        $this->createSystemConfigsTable();

        SystemConfigGroupService::installInitialize([
            [
                'title' => '基础配置',
                'name' => 'basic',
                'type' => 'addon',
                'access' => 'private',
                'sort' => 100,
                'status' => 1,
                'fields' => [
                    [
                        'title' => '站点标题',
                        'name' => 'title',
                        'type' => 'text',
                        'value' => 'CMS Demo',
                        'default_val' => 'CMS Demo',
                        'sort' => 100,
                    ],
                ],
            ],
        ], 'cms');

        SystemConfigService::updateSystemConfigCache();

        self::assertSame('CMS Demo', SystemConfigService::addonValue('cms', 'basic.title'));
        self::assertSame('CMS Demo', SystemConfigService::value('cms::basic.title'));
    }

    public function test_install_initialize_defaults_is_system_to_one_for_groups_and_fields(): void
    {
        $this->createSystemConfigGroupsTable();
        $this->createSystemConfigsTable();

        SystemConfigGroupService::installInitialize([
            [
                'title' => '基础设置',
                'name' => 'basic',
                'type' => 'system',
                'access' => 'public',
                'sort' => 100,
                'status' => 1,
                'fields' => [
                    [
                        'title' => '站点标题',
                        'name' => 'site_title',
                        'type' => 'text',
                        'value' => 'PTAdmin',
                        'default_val' => 'PTAdmin',
                        'sort' => 100,
                    ],
                ],
            ],
        ]);

        self::assertSame(1, (int) SystemConfigGroup::query()->where('name', 'basic')->value('is_system'));
        self::assertSame(1, (int) SystemConfig::query()->where('name', 'site_title')->value('is_system'));
    }

    public function test_system_config_preset_matches_current_flat_group_structure(): void
    {
        $definitions = SystemConfigPreset::definitions();

        self::assertNotEmpty($definitions);
        self::assertSame('basic', $definitions[0]['name']);
        self::assertArrayNotHasKey('children', $definitions[0]);
        self::assertArrayHasKey('type', $definitions[0]);
        self::assertArrayHasKey('access', $definitions[0]);
        self::assertArrayHasKey('fields', $definitions[0]);
        self::assertSame('site_title', $definitions[0]['fields'][0]['name']);
    }
}
