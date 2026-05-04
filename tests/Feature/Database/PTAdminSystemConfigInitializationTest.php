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
            'meta' => [],
        ], $config->extra);
        self::assertSame("top_left=左上\nbottom_left=左下", $config->extra_value);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('配置项键名重复，请规范填写');

        $duplicate = new SystemConfig();
        $duplicate->extra = "same=一\nsame=二";
    }

    public function test_system_config_model_extra_attribute_supports_metadata_payload(): void
    {
        $config = new SystemConfig();
        $config->extra = [
            'options' => [
                'public' => '公开',
                'private' => '私有',
            ],
            'meta' => [
                'placeholder' => '请选择可见性',
                'expose' => 'private',
            ],
        ];

        self::assertSame([
            'options' => [
                'public' => '公开',
                'private' => '私有',
            ],
            'meta' => [
                'placeholder' => '请选择可见性',
                'expose' => 'private',
            ],
        ], $config->extra);
    }

    public function test_system_config_install_initialize_rejects_third_level_groups(): void
    {
        $this->createSystemConfigGroupsTable();
        $this->createSystemConfigsTable();

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('系统配置分组最多只支持两级结构');

        SystemConfigGroupService::installInitialize([
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
                        'children' => [
                            [
                                'title' => '第三层',
                                'name' => 'deep',
                                'weight' => 80,
                                'status' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_system_config_install_initialize_rejects_invalid_group_layout_mode(): void
    {
        $this->createSystemConfigGroupsTable();
        $this->createSystemConfigsTable();

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('配置分组布局仅支持 tab 或 block');

        SystemConfigGroupService::installInitialize([
            [
                'title' => '系统配置',
                'name' => 'system',
                'weight' => 100,
                'extra' => [
                    'layout' => [
                        'mode' => 'grid',
                    ],
                ],
                'status' => 1,
            ],
        ]);
    }

    public function test_system_config_install_initialize_rejects_invalid_field_expose_mode(): void
    {
        $this->createSystemConfigGroupsTable();
        $this->createSystemConfigsTable();

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('配置字段 expose 仅支持 public、protected、private');

        SystemConfigGroupService::installInitialize([
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
                                'extra' => [
                                    'meta' => [
                                        'expose' => 'global',
                                    ],
                                ],
                                'value' => 'PTAdmin',
                                'default_val' => 'PTAdmin',
                                'weight' => 100,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_system_config_install_initialize_rejects_invalid_field_type(): void
    {
        $this->createSystemConfigGroupsTable();
        $this->createSystemConfigsTable();

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('配置字段 type 仅支持 text、textarea、switch、radio、checkbox、select、json、password');

        SystemConfigGroupService::installInitialize([
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
                                'type' => 'number',
                                'value' => 'PTAdmin',
                                'default_val' => 'PTAdmin',
                                'weight' => 100,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_system_config_install_initialize_rejects_unsupported_meta_for_field_type(): void
    {
        $this->createSystemConfigGroupsTable();
        $this->createSystemConfigsTable();

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('配置字段 type[switch]不支持 meta[placeholder]');

        SystemConfigGroupService::installInitialize([
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
                                'title' => '登录验证码',
                                'name' => 'login_captcha',
                                'type' => 'switch',
                                'extra' => [
                                    'meta' => [
                                        'placeholder' => '不应该出现',
                                    ],
                                ],
                                'value' => '1',
                                'default_val' => '0',
                                'weight' => 100,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_system_config_install_initialize_rejects_invalid_meta_value_shape(): void
    {
        $this->createSystemConfigGroupsTable();
        $this->createSystemConfigsTable();

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('配置字段 meta[required]的值格式不正确');

        SystemConfigGroupService::installInitialize([
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
                                'extra' => [
                                    'meta' => [
                                        'required' => 'sometimes',
                                    ],
                                ],
                                'value' => 'PTAdmin',
                                'default_val' => 'PTAdmin',
                                'weight' => 100,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_system_config_preset_contains_upload_storage_defaults(): void
    {
        $this->createSystemConfigGroupsTable();
        $this->createSystemConfigsTable();

        SystemConfigGroupService::installInitialize(SystemConfigPreset::definitions());
        SystemConfigService::updateSystemConfigCache();

        self::assertNotNull(SystemConfigGroup::query()->where('name', 'upload')->first());
        self::assertNotNull(SystemConfigGroup::query()->where('name', 'security')->first());
        self::assertNotNull(SystemConfigGroup::query()->where('name', 'oauth')->where('parent_id', 0)->first());
        self::assertNotNull(SystemConfigGroup::query()->where('name', 'wechat')->first());
        self::assertSame('local', SystemConfig::query()->where('name', 'storage_driver')->value('value'));
        self::assertSame('oss', SystemConfig::query()->where('name', 'storage_disk')->value('value'));
        self::assertSame('public', SystemConfig::query()->where('name', 'storage_visibility')->value('value'));
        self::assertSame('1', SystemConfig::query()->where('name', 'is_register')->value('value'));
        self::assertSame('0', SystemConfig::query()->where('name', 'active')->where('system_config_group_id', SystemConfigGroup::query()->where('name', 'wechat')->value('id'))->value('value'));
        self::assertSame('local', system_config('system.upload.storage_driver'));
        self::assertSame('oss', system_config('upload.storage_disk'));
        self::assertSame([], system_config('upload.storage_meta'));
        self::assertSame(1, system_config('system.security.is_register'));
        self::assertSame([
            'active' => 0,
            'app_id' => '',
            'app_secret' => '',
            'redirect' => '',
        ], system_config('oauth.wechat'));
        self::assertSame([
            'system.basic.site_title' => 'PTAdmin',
            'system.basic.site_description' => '',
        ], public_system_config());

        $group = SystemConfigGroup::query()->where('name', 'system')->where('parent_id', 0)->firstOrFail();
        $section = SystemConfigGroup::query()->where('name', 'basic')->where('parent_id', $group->id)->firstOrFail();
        $payload = app(SystemConfigService::class)->section((int) $section->id);
        $fieldMap = [];
        foreach ($payload['schema']['fields'] as $field) {
            $fieldMap[$field['name']] = $field;
        }

        self::assertSame('tab', $payload['group']['extra']['layout']['mode']);
        self::assertSame('block', $payload['section']['extra']['layout']['mode']);
        self::assertSame('block', $payload['schema']['layout']['mode']);
        self::assertSame('请输入站点标题', $fieldMap['site_title']['metadata']['placeholder']);
        self::assertSame('public', $fieldMap['site_title']['metadata']['expose']);
        self::assertSame('private', $fieldMap['login_captcha']['metadata']['expose']);
    }
}
