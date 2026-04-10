<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Database;

use PTAdmin\Admin\Models\SystemConfig;
use PTAdmin\Admin\Models\SystemConfigGroup;
use PTAdmin\Admin\Services\SystemConfigGroupService;
use PTAdmin\Admin\Services\SystemConfigService;
use PTAdmin\Admin\Support\SystemConfigPreset;
use PTAdmin\Admin\Tests\TestCase;
use PTAdmin\Foundation\Exceptions\ServiceException;

class PTAdminSystemConfigInitializationTest extends TestCase
{
    public function test_system_config_install_initialize_is_idempotent_and_updates_existing_records(): void
    {
        $this->createSystemConfigGroupsTable();
        $this->createSystemConfigsTable();

        $initial = [
            [
                'title' => '系统配置',
                'name' => 'system',
                'weight' => 100,
                'status' => 1,
                'children' => [
                    [
                        'title' => '基础配置',
                        'name' => 'basic',
                        'weight' => 90,
                        'status' => 1,
                        'fields' => [
                            [
                                'title' => '站点标题',
                                'name' => 'site_title',
                                'type' => 'text',
                                'value' => 'PTAdmin',
                                'default_val' => 'PTAdmin',
                                'weight' => 100,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        SystemConfigGroupService::installInitialize($initial);

        $updated = [
            [
                'title' => '系统配置更新',
                'name' => 'system',
                'weight' => 120,
                'status' => 1,
                'children' => [
                    [
                        'title' => '基础配置更新',
                        'name' => 'basic',
                        'weight' => 95,
                        'status' => 1,
                        'fields' => [
                            [
                                'title' => '站点标题更新',
                                'name' => 'site_title',
                                'type' => 'text',
                                'value' => 'PTAdmin Next',
                                'default_val' => 'PTAdmin Next',
                                'weight' => 110,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        SystemConfigGroupService::installInitialize($updated);

        self::assertSame(2, SystemConfigGroup::query()->count());
        self::assertSame(1, SystemConfig::query()->count());
        self::assertSame('系统配置更新', SystemConfigGroup::query()->where('name', 'system')->value('title'));
        self::assertSame('基础配置更新', SystemConfigGroup::query()->where('name', 'basic')->value('title'));
        self::assertSame('站点标题更新', SystemConfig::query()->where('name', 'site_title')->value('title'));
        self::assertSame('PTAdmin Next', SystemConfig::query()->where('name', 'site_title')->value('value'));
    }

    public function test_system_config_model_extra_attribute_normalizes_lines_and_rejects_duplicate_keys(): void
    {
        $config = new SystemConfig();
        $config->extra = "top_left=左上\n\nbottom_left=左下";

        self::assertSame([
            'options' => [
                'top_left' => '左上',
                'bottom_left' => '左下',
            ],
        ], $config->extra);
        self::assertSame("top_left=左上\nbottom_left=左下", $config->extra_value);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('配置项键名重复，请规范填写');

        $duplicate = new SystemConfig();
        $duplicate->extra = "same=一\nsame=二";
    }

    public function test_system_config_preset_contains_upload_storage_defaults(): void
    {
        $this->createSystemConfigGroupsTable();
        $this->createSystemConfigsTable();

        SystemConfigGroupService::installInitialize(SystemConfigPreset::definitions());
        SystemConfigService::updateSystemConfigCache();

        self::assertNotNull(SystemConfigGroup::query()->where('name', 'upload')->first());
        self::assertSame('local', SystemConfig::query()->where('name', 'storage_driver')->value('value'));
        self::assertSame('oss', SystemConfig::query()->where('name', 'storage_disk')->value('value'));
        self::assertSame('public', SystemConfig::query()->where('name', 'storage_visibility')->value('value'));
        self::assertSame('local', system_config('system.upload.storage_driver'));
        self::assertSame('oss', system_config('upload.storage_disk'));
        self::assertSame([], system_config('upload.storage_meta'));
    }
}
