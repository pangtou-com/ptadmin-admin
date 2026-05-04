<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use Illuminate\Support\Facades\File;
use PTAdmin\Addon\Addon;
use PTAdmin\Addon\Service\AddonManager;
use PTAdmin\Admin\Models\SystemConfig;
use PTAdmin\Admin\Models\SystemConfigGroup;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminSettingsApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        File::deleteDirectory(base_path('addons'));
        File::delete(base_path('bootstrap/cache/addons.php'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path('addons'));
        File::delete(base_path('bootstrap/cache/addons.php'));
        Addon::swap(new AddonManager());

        parent::tearDown();
    }

    public function test_system_settings_facade_catalog_detail_and_save_work(): void
    {
        [, $section] = $this->seedSystemSettingFixtures();
        $token = $this->issueFounderToken();

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/system/catalog')
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'scope' => 'system',
                    'owner' => [
                        'code' => 'system',
                        'name' => '系统设置',
                    ],
                    'sections' => [
                        [
                            'key' => 'basic',
                            'title' => '基础配置',
                            'mode' => 'hosted',
                            'icon' => 'Setting',
                        ],
                    ],
                ],
            ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/system/sections/basic')
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'scope' => 'system',
                    'owner' => [
                        'code' => 'system',
                    ],
                    'section' => [
                        'key' => 'basic',
                        'title' => '基础配置',
                        'extra' => [
                            'layout' => [
                                'mode' => 'block',
                                'labelWidth' => 140,
                            ],
                        ],
                    ],
                    'values' => [
                        'site_title' => 'PTAdmin',
                        'login_captcha' => 1,
                    ],
                ],
            ])
            ->assertJsonPath('data.render.schema.layout.mode', 'block')
            ->assertJsonPath('data.render.schema.fields.0.placeholder', '请输入站点标题')
            ->assertJsonPath('data.render.schema.fields.0.metadata.placeholder', '请输入站点标题')
            ->assertJsonPath('data.render.schema.fields.0.metadata.expose', 'public')
            ->assertJsonPath('data.render.schema.fields.1.type', 'switch');

        $saveResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/settings/system/sections/basic', [
                'values' => [
                    'site_title' => 'PTAdmin Next',
                    'login_captcha' => 0,
                ],
            ]);

        $saveResponse->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'values' => [
                    'site_title' => 'PTAdmin Next',
                    'login_captcha' => 0,
                ],
            ],
        ]);

        self::assertSame('PTAdmin Next', SystemConfig::query()->where('name', 'site_title')->value('value'));
        self::assertSame('0', SystemConfig::query()->where('name', 'login_captcha')->value('value'));
        self::assertSame((int) $section->id, (int) SystemConfig::query()->where('name', 'site_title')->value('system_config_group_id'));
    }

    public function test_system_settings_facade_supports_multiple_root_groups_via_composite_section_key(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createSystemConfigGroupsTable();
        $this->createSystemConfigsTable();
        $this->migratePackageTables();

        $systemRoot = SystemConfigGroup::query()->create([
            'title' => '系统设置',
            'name' => 'system',
            'weight' => 100,
            'status' => 1,
        ]);
        SystemConfigGroup::query()->create([
            'title' => '基础配置',
            'name' => 'basic',
            'parent_id' => $systemRoot->id,
            'weight' => 100,
            'status' => 1,
        ]);

        $oauthRoot = SystemConfigGroup::query()->create([
            'title' => '第三方登录',
            'name' => 'oauth',
            'weight' => 90,
            'status' => 1,
        ]);
        $wechatSection = SystemConfigGroup::query()->create([
            'title' => '微信登录',
            'name' => 'wechat',
            'parent_id' => $oauthRoot->id,
            'weight' => 100,
            'intro' => '微信授权登录参数',
            'status' => 1,
        ]);
        SystemConfig::query()->create([
            'title' => '启用授权登录',
            'name' => 'active',
            'system_config_group_id' => $wechatSection->id,
            'weight' => 100,
            'type' => 'switch',
            'value' => '0',
            'default_val' => '0',
        ]);
        SystemConfig::query()->create([
            'title' => '应用 ID',
            'name' => 'app_id',
            'system_config_group_id' => $wechatSection->id,
            'weight' => 90,
            'type' => 'text',
            'value' => '',
            'default_val' => '',
        ]);

        $token = $this->issueFounderToken();

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/system/catalog')
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'scope' => 'system',
                    'owner' => [
                        'code' => 'system',
                        'name' => '系统设置',
                    ],
                    'sections' => [
                        [
                            'key' => 'basic',
                        ],
                        [
                            'key' => 'oauth.wechat',
                            'title' => '第三方登录 / 微信登录',
                        ],
                    ],
                ],
            ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/system/sections/oauth.wechat')
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'scope' => 'system',
                    'owner' => [
                        'code' => 'system',
                        'name' => '系统设置',
                    ],
                    'section' => [
                        'key' => 'oauth.wechat',
                        'title' => '第三方登录 / 微信登录',
                    ],
                    'values' => [
                        'active' => 0,
                        'app_id' => '',
                    ],
                ],
            ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/settings/system/sections/oauth.wechat', [
                'values' => [
                    'active' => 1,
                    'app_id' => 'wx-demo',
                ],
            ])
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'values' => [
                        'active' => 1,
                        'app_id' => 'wx-demo',
                    ],
                ],
            ]);
    }

    public function test_system_settings_facade_rejects_invalid_option_value_on_save(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createSystemConfigGroupsTable();
        $this->createSystemConfigsTable();
        $this->migratePackageTables();

        $group = SystemConfigGroup::query()->create([
            'title' => '系统设置',
            'name' => 'system',
            'weight' => 100,
            'status' => 1,
        ]);
        $section = SystemConfigGroup::query()->create([
            'title' => '上传配置',
            'name' => 'upload',
            'parent_id' => $group->id,
            'weight' => 90,
            'status' => 1,
        ]);
        SystemConfig::query()->create([
            'title' => '上传可见性',
            'name' => 'storage_visibility',
            'system_config_group_id' => $section->id,
            'weight' => 100,
            'type' => 'radio',
            'extra' => [
                'options' => [
                    'public' => '公开',
                    'private' => '私有',
                ],
            ],
            'value' => 'public',
            'default_val' => 'public',
        ]);

        $token = $this->issueFounderToken();

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/settings/system/sections/upload', [
                'values' => [
                    'storage_visibility' => 'internal',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('code', 10000)
            ->assertJsonPath('message', '配置字段[storage_visibility]的值不在允许选项中');
    }

    public function test_system_settings_facade_rejects_required_and_pattern_invalid_values_on_save(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createSystemConfigGroupsTable();
        $this->createSystemConfigsTable();
        $this->migratePackageTables();

        $group = SystemConfigGroup::query()->create([
            'title' => '系统设置',
            'name' => 'system',
            'weight' => 100,
            'status' => 1,
        ]);
        $section = SystemConfigGroup::query()->create([
            'title' => '基础配置',
            'name' => 'basic',
            'parent_id' => $group->id,
            'weight' => 90,
            'status' => 1,
        ]);
        SystemConfig::query()->create([
            'title' => '站点标识',
            'name' => 'site_code',
            'system_config_group_id' => $section->id,
            'weight' => 100,
            'type' => 'text',
            'extra' => [
                'meta' => [
                    'required' => true,
                    'pattern' => '/^[a-z0-9_-]+$/',
                    'min' => 3,
                    'max' => 12,
                ],
            ],
            'value' => 'ptadmin',
            'default_val' => 'ptadmin',
        ]);

        $token = $this->issueFounderToken();

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/settings/system/sections/basic', [
                'values' => [
                    'site_code' => '',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('code', 10000)
            ->assertJsonPath('message', '配置字段[site_code]不能为空');

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/settings/system/sections/basic', [
                'values' => [
                    'site_code' => 'A#',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('code', 10000)
            ->assertJsonPath('message', '配置字段[site_code]长度不能小于3');
    }

    public function test_plugin_settings_facade_catalog_detail_and_save_work_with_registered_hosted_settings(): void
    {
        $this->migratePackageTables();
        $token = $this->issueFounderToken();

        $this->writeAddonWithSettings('Cms', [
            'id' => 'cms',
            'code' => 'cms',
            'name' => '内容管理系统',
            'title' => '内容管理系统',
            'version' => '1.0.0',
            'providers' => [],
        ], [
            'enabled' => true,
            'mode' => 'hosted',
            'icon' => 'Document',
            'sections' => [
                [
                    'key' => 'basic',
                    'title' => '基础配置',
                    'description' => '内容管理系统基础配置',
                    'order' => 10,
                    'schema' => [
                        'name' => 'cms_basic_settings',
                        'title' => '基础配置',
                        'layout' => [
                            'mode' => 'block',
                            'labelWidth' => 140,
                        ],
                        'fields' => [
                            [
                                'name' => 'site_name',
                                'type' => 'text',
                                'label' => '站点名称',
                                'meta' => [
                                    'placeholder' => '请输入站点名称',
                                ],
                            ],
                            [
                                'name' => 'enabled',
                                'type' => 'switch',
                                'label' => '启用状态',
                            ],
                        ],
                    ],
                    'defaults' => [
                        'site_name' => 'CMS Demo',
                        'enabled' => true,
                    ],
                ],
            ],
        ]);

        Addon::swap(new AddonManager());

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/plugins/catalog')
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'scope' => 'plugin',
                    'results' => [
                        [
                            'owner' => [
                                'code' => 'cms',
                                'name' => '内容管理系统',
                            ],
                            'settings' => [
                                'enabled' => true,
                                'mode' => 'hosted',
                                'managed_by' => 'system',
                                'injection' => [
                                    'strategy' => 'merge',
                                ],
                                'cleanup' => [
                                    'on_uninstall' => 'retain',
                                ],
                                'sections' => [
                                    [
                                        'key' => 'basic',
                                        'title' => '基础配置',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $detailResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/plugins/cms/sections/basic');

        $detailResponse
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'scope' => 'plugin',
                    'owner' => [
                        'code' => 'cms',
                        'name' => '内容管理系统',
                    ],
                    'section' => [
                        'key' => 'basic',
                        'title' => '基础配置',
                        'extra' => [
                            'managed_by' => 'system',
                        ],
                    ],
                    'values' => [
                        'site_name' => 'CMS Demo',
                        'enabled' => 1,
                    ],
                ],
            ])
            ->assertJsonPath('data.render.schema.layout.mode', 'block')
            ->assertJsonPath('data.render.schema.fields.0.type', 'text')
            ->assertJsonPath('data.render.schema.fields.0.meta.placeholder', '请输入站点名称')
            ->assertJsonPath('data.render.schema.fields.1.type', 'switch');

        $detailContent = (string) $detailResponse->getContent();
        self::assertStringNotContainsString('"component"', $detailContent);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/settings/plugins/cms/sections/basic', [
                'values' => [
                    'site_name' => 'CMS Hosted',
                    'enabled' => false,
                ],
            ])
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'values' => [
                        'site_name' => 'CMS Hosted',
                        'enabled' => 0,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('system_config_groups', [
            'addon_code' => 'cms',
            'name' => 'addon_cms_basic',
        ]);
        $this->assertDatabaseHas('system_configs', [
            'name' => 'site_name',
            'value' => 'CMS Hosted',
        ]);
    }

    public function test_plugin_settings_facade_rejects_invalid_checkbox_value_on_save(): void
    {
        $this->migratePackageTables();
        $token = $this->issueFounderToken();

        $this->writeAddonWithSettings('Cms', [
            'id' => 'cms',
            'code' => 'cms',
            'name' => '内容管理系统',
            'title' => '内容管理系统',
            'version' => '1.0.0',
            'providers' => [],
        ], [
            'enabled' => true,
            'mode' => 'hosted',
            'sections' => [
                [
                    'key' => 'basic',
                    'title' => '基础配置',
                    'schema' => [
                        'fields' => [
                            [
                                'name' => 'notify_channels',
                                'type' => 'checkbox',
                                'label' => '通知渠道',
                                'options' => [
                                    [
                                        'label' => '邮件',
                                        'value' => 'mail',
                                    ],
                                    [
                                        'label' => '短信',
                                        'value' => 'sms',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'defaults' => [
                        'notify_channels' => ['mail'],
                    ],
                ],
            ],
        ]);

        Addon::swap(new AddonManager());

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/settings/plugins/cms/sections/basic', [
                'values' => [
                    'notify_channels' => 'mail',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('code', 10000)
            ->assertJsonPath('message', '配置字段[notify_channels]仅支持数组值');
    }

    public function test_plugin_settings_facade_rejects_scalar_meta_rules_on_save(): void
    {
        $this->migratePackageTables();
        $token = $this->issueFounderToken();

        $this->writeAddonWithSettings('Cms', [
            'id' => 'cms',
            'code' => 'cms',
            'name' => '内容管理系统',
            'title' => '内容管理系统',
            'version' => '1.0.0',
            'providers' => [],
        ], [
            'enabled' => true,
            'mode' => 'hosted',
            'sections' => [
                [
                    'key' => 'basic',
                    'title' => '基础配置',
                    'schema' => [
                        'fields' => [
                            [
                                'name' => 'site_code',
                                'type' => 'text',
                                'label' => '站点标识',
                                'meta' => [
                                    'required' => true,
                                    'pattern' => '/^[a-z0-9_-]+$/',
                                    'min' => 3,
                                ],
                            ],
                        ],
                    ],
                    'defaults' => [
                        'site_code' => 'cms',
                    ],
                ],
            ],
        ]);

        Addon::swap(new AddonManager());

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/settings/plugins/cms/sections/basic', [
                'values' => [
                    'site_code' => 'A#',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('code', 10000)
            ->assertJsonPath('message', '配置字段[site_code]长度不能小于3');

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/settings/plugins/cms/sections/basic', [
                'values' => [
                    'site_code' => '中文站点',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('code', 10000)
            ->assertJsonPath('message', '配置字段[site_code]格式不正确');
    }

    public function test_plugin_settings_facade_blocks_system_save_when_plugin_is_self_managed(): void
    {
        $this->migratePackageTables();
        $token = $this->issueFounderToken();

        $this->writeAddonWithSettings('Cms', [
            'id' => 'cms',
            'code' => 'cms',
            'name' => '内容管理系统',
            'title' => '内容管理系统',
            'version' => '1.0.0',
            'providers' => [],
        ], [
            'enabled' => true,
            'mode' => 'hosted',
            'managed_by' => 'plugin',
            'cleanup' => [
                'on_uninstall' => 'purge',
            ],
            'sections' => [
                [
                    'key' => 'basic',
                    'title' => '基础配置',
                    'schema' => [
                        'fields' => [
                            [
                                'name' => 'site_name',
                                'type' => 'text',
                                'label' => '站点名称',
                            ],
                        ],
                    ],
                    'defaults' => [
                        'site_name' => 'CMS Demo',
                    ],
                ],
            ],
        ]);

        Addon::swap(new AddonManager());

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/plugins/catalog')
            ->assertOk()
            ->assertJsonPath('data.results.0.settings.managed_by', 'plugin')
            ->assertJsonPath('data.results.0.settings.cleanup.on_uninstall', 'purge');

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/plugins/cms/sections/basic')
            ->assertOk()
            ->assertJsonPath('data.meta.editable', false)
            ->assertJsonPath('data.section.extra.managed_by', 'plugin');

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/settings/plugins/cms/sections/basic', [
                'values' => [
                    'site_name' => 'CMS Hosted',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('code', 10000)
            ->assertJsonPath('message', '插件[cms]当前由插件自身管理配置，不允许通过系统设置中心保存');
    }

    public function test_plugin_settings_facade_reuses_legacy_addon_config_storage(): void
    {
        $this->migratePackageTables();
        $token = $this->issueFounderToken();

        $this->writeAddonWithLegacyConfig('Cms', [
            'id' => 'cms',
            'code' => 'cms',
            'name' => '内容管理系统',
            'title' => '内容管理系统',
            'version' => '1.0.0',
            'providers' => [],
        ], [
            'admin_route_prefix' => 'cms',
            'api_route_prefix' => 'api/cms',
        ]);

        Addon::swap(new AddonManager());

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/plugins/catalog')
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'scope' => 'plugin',
                    'results' => [
                        [
                            'owner' => [
                                'code' => 'cms',
                                'name' => '内容管理系统',
                            ],
                            'settings' => [
                                'enabled' => true,
                                'mode' => 'hosted',
                                'sections' => [
                                    [
                                        'key' => 'basic',
                                        'title' => '基础配置',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/plugins/cms/sections/basic')
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'scope' => 'plugin',
                    'owner' => [
                        'code' => 'cms',
                        'name' => '内容管理系统',
                    ],
                    'section' => [
                        'key' => 'basic',
                        'title' => '基础配置',
                    ],
                    'values' => [
                        'admin_route_prefix' => 'cms',
                        'api_route_prefix' => 'api/cms',
                    ],
                ],
            ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/settings/plugins/cms/sections/basic', [
                'values' => [
                    'admin_route_prefix' => 'cms-admin',
                ],
            ])
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'values' => [
                        'admin_route_prefix' => 'cms-admin',
                        'api_route_prefix' => 'api/cms',
                    ],
                ],
            ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/addons/cms/config')
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'values' => [
                        'admin_route_prefix' => 'cms-admin',
                        'api_route_prefix' => 'api/cms',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('system_config_groups', [
            'addon_code' => 'cms',
            'name' => 'basic',
        ]);
        $this->assertDatabaseMissing('system_config_groups', [
            'addon_code' => 'cms',
            'name' => 'addon_cms_basic',
        ]);
    }

    public function test_plugin_settings_facade_supports_external_route_mode_in_catalog(): void
    {
        $this->migratePackageTables();
        $token = $this->issueFounderToken();

        $this->writeAddonWithSettings('Cms', [
            'id' => 'cms',
            'code' => 'cms',
            'name' => '内容管理系统',
            'title' => '内容管理系统',
            'version' => '1.0.0',
            'providers' => [],
        ], [
            'enabled' => true,
            'mode' => 'external_route',
            'icon' => 'Link',
            'path' => '/cms/settings',
            'sections' => [],
        ]);

        Addon::swap(new AddonManager());

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/plugins/catalog')
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'scope' => 'plugin',
                    'results' => [
                        [
                            'owner' => [
                                'code' => 'cms',
                                'name' => '内容管理系统',
                            ],
                            'settings' => [
                                'enabled' => true,
                                'mode' => 'external_route',
                                'path' => '/cms/settings',
                                'sections' => [],
                            ],
                        ],
                    ],
                ],
            ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/plugins/cms/sections/basic')
            ->assertOk()
            ->assertJsonPath('code', 10000)
            ->assertJsonPath('message', '插件[cms]当前使用 external_route 模式，不提供 hosted settings section');
    }

    public function test_plugin_settings_facade_excludes_none_mode_from_catalog(): void
    {
        $this->migratePackageTables();
        $token = $this->issueFounderToken();

        $this->writeAddonWithSettings('Cms', [
            'id' => 'cms',
            'code' => 'cms',
            'name' => '内容管理系统',
            'title' => '内容管理系统',
            'version' => '1.0.0',
            'providers' => [],
        ], [
            'enabled' => true,
            'mode' => 'none',
            'sections' => [],
        ]);

        Addon::swap(new AddonManager());

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/plugins/catalog')
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'scope' => 'plugin',
                    'results' => [],
                ],
            ])
            ->assertJsonCount(0, 'data.results');
    }

    public function test_plugin_settings_facade_rejects_external_route_mode_without_path(): void
    {
        $this->migratePackageTables();
        $token = $this->issueFounderToken();

        $this->writeAddonWithSettings('Cms', [
            'id' => 'cms',
            'code' => 'cms',
            'name' => '内容管理系统',
            'title' => '内容管理系统',
            'version' => '1.0.0',
            'providers' => [],
        ], [
            'enabled' => true,
            'mode' => 'external_route',
            'sections' => [],
        ]);

        Addon::swap(new AddonManager());

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/plugins/catalog')
            ->assertOk()
            ->assertJsonPath('code', 10000)
            ->assertJsonPath('message', '插件[cms]在 external_route 模式下必须提供非空 path');
    }

    public function test_plugin_settings_facade_rejects_unsupported_meta_for_field_type_on_registration(): void
    {
        $this->migratePackageTables();
        $token = $this->issueFounderToken();

        $this->writeAddonWithSettings('Cms', [
            'id' => 'cms',
            'code' => 'cms',
            'name' => '内容管理系统',
            'title' => '内容管理系统',
            'version' => '1.0.0',
            'providers' => [],
        ], [
            'enabled' => true,
            'mode' => 'hosted',
            'sections' => [
                [
                    'key' => 'basic',
                    'title' => '基础配置',
                    'schema' => [
                        'fields' => [
                            [
                                'name' => 'enabled',
                                'type' => 'switch',
                                'label' => '启用状态',
                                'meta' => [
                                    'placeholder' => '不支持',
                                ],
                            ],
                        ],
                    ],
                    'defaults' => [
                        'enabled' => true,
                    ],
                ],
            ],
        ]);

        Addon::swap(new AddonManager());

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/plugins/catalog')
            ->assertOk()
            ->assertJsonPath('code', 10000)
            ->assertJsonPath('message', '插件[cms]的 section[basic] 字段[enabled] type[switch]不支持 meta[placeholder]');
    }

    public function test_plugin_settings_facade_rejects_invalid_meta_value_shape_on_registration(): void
    {
        $this->migratePackageTables();
        $token = $this->issueFounderToken();

        $this->writeAddonWithSettings('Cms', [
            'id' => 'cms',
            'code' => 'cms',
            'name' => '内容管理系统',
            'title' => '内容管理系统',
            'version' => '1.0.0',
            'providers' => [],
        ], [
            'enabled' => true,
            'mode' => 'hosted',
            'sections' => [
                [
                    'key' => 'basic',
                    'title' => '基础配置',
                    'schema' => [
                        'fields' => [
                            [
                                'name' => 'site_name',
                                'type' => 'text',
                                'label' => '站点名称',
                                'meta' => [
                                    'required' => 'sometimes',
                                ],
                            ],
                        ],
                    ],
                    'defaults' => [
                        'site_name' => 'CMS Demo',
                    ],
                ],
            ],
        ]);

        Addon::swap(new AddonManager());

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/plugins/catalog')
            ->assertOk()
            ->assertJsonPath('code', 10000)
            ->assertJsonPath('message', '插件[cms]的 section[basic] 字段[site_name] meta[required]的值格式不正确');
    }

    public function test_plugin_settings_facade_rejects_duplicate_section_keys(): void
    {
        $this->migratePackageTables();
        $token = $this->issueFounderToken();

        $this->writeAddonWithSettings('Cms', [
            'id' => 'cms',
            'code' => 'cms',
            'name' => '内容管理系统',
            'title' => '内容管理系统',
            'version' => '1.0.0',
            'providers' => [],
        ], [
            'enabled' => true,
            'mode' => 'hosted',
            'sections' => [
                [
                    'key' => 'basic',
                    'title' => '基础配置',
                    'schema' => [
                        'fields' => [
                            [
                                'name' => 'site_name',
                                'type' => 'text',
                                'label' => '站点名称',
                            ],
                        ],
                    ],
                    'defaults' => [
                        'site_name' => 'CMS Demo',
                    ],
                ],
                [
                    'key' => 'basic',
                    'title' => '重复配置',
                    'schema' => [
                        'fields' => [
                            [
                                'name' => 'app_name',
                                'type' => 'text',
                                'label' => '应用名称',
                            ],
                        ],
                    ],
                    'defaults' => [
                        'app_name' => 'CMS App',
                    ],
                ],
            ],
        ]);

        Addon::swap(new AddonManager());

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/plugins/catalog')
            ->assertOk()
            ->assertJsonPath('code', 10000)
            ->assertJsonPath('message', '插件[cms]的 section[basic] 重复定义');
    }

    public function test_plugin_settings_facade_rejects_defaults_field_not_declared_in_schema(): void
    {
        $this->migratePackageTables();
        $token = $this->issueFounderToken();

        $this->writeAddonWithSettings('Cms', [
            'id' => 'cms',
            'code' => 'cms',
            'name' => '内容管理系统',
            'title' => '内容管理系统',
            'version' => '1.0.0',
            'providers' => [],
        ], [
            'enabled' => true,
            'mode' => 'hosted',
            'sections' => [
                [
                    'key' => 'basic',
                    'title' => '基础配置',
                    'schema' => [
                        'fields' => [
                            [
                                'name' => 'site_name',
                                'type' => 'text',
                                'label' => '站点名称',
                            ],
                        ],
                    ],
                    'defaults' => [
                        'site_name' => 'CMS Demo',
                        'ghost_field' => 'invalid',
                    ],
                ],
            ],
        ]);

        Addon::swap(new AddonManager());

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/plugins/catalog')
            ->assertOk()
            ->assertJsonPath('code', 10000)
            ->assertJsonPath('message', '插件[cms]的 section[basic] defaults 字段[ghost_field]未在 schema.fields 中声明');
    }

    public function test_plugin_settings_facade_skip_strategy_keeps_existing_schema_and_values(): void
    {
        $this->migratePackageTables();
        $token = $this->issueFounderToken();

        $this->writeAddonWithSettings('Cms', [
            'id' => 'cms',
            'code' => 'cms',
            'name' => '内容管理系统',
            'title' => '内容管理系统',
            'version' => '1.0.0',
            'providers' => [],
        ], [
            'enabled' => true,
            'mode' => 'hosted',
            'injection' => [
                'strategy' => 'merge',
            ],
            'sections' => [
                [
                    'key' => 'basic',
                    'title' => '基础配置',
                    'schema' => [
                        'fields' => [
                            [
                                'name' => 'site_name',
                                'type' => 'text',
                                'label' => '站点名称',
                                'meta' => [
                                    'placeholder' => '旧占位',
                                ],
                            ],
                        ],
                    ],
                    'defaults' => [
                        'site_name' => 'Old Name',
                    ],
                ],
            ],
        ]);

        Addon::swap(new AddonManager());

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/plugins/cms/sections/basic')
            ->assertOk()
            ->assertJsonPath('data.render.schema.fields.0.meta.placeholder', '旧占位')
            ->assertJsonPath('data.values.site_name', 'Old Name');

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/settings/plugins/cms/sections/basic', [
                'values' => [
                    'site_name' => 'Persisted Name',
                ],
            ])
            ->assertOk();

        $this->writeAddonWithSettings('Cms', [
            'id' => 'cms',
            'code' => 'cms',
            'name' => '内容管理系统',
            'title' => '内容管理系统',
            'version' => '1.0.1',
            'providers' => [],
        ], [
            'enabled' => true,
            'mode' => 'hosted',
            'injection' => [
                'strategy' => 'skip',
            ],
            'sections' => [
                [
                    'key' => 'basic',
                    'title' => '基础配置更新',
                    'schema' => [
                        'fields' => [
                            [
                                'name' => 'site_name',
                                'type' => 'text',
                                'label' => '站点名称',
                                'meta' => [
                                    'placeholder' => '新占位',
                                ],
                            ],
                        ],
                    ],
                    'defaults' => [
                        'site_name' => 'New Name',
                    ],
                ],
            ],
        ]);

        Addon::swap(new AddonManager());

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/plugins/cms/sections/basic')
            ->assertOk()
            ->assertJsonPath('data.section.title', '基础配置')
            ->assertJsonPath('data.render.schema.fields.0.meta.placeholder', '旧占位')
            ->assertJsonPath('data.values.site_name', 'Persisted Name');
    }

    public function test_plugin_settings_facade_overwrite_strategy_removes_orphan_fields(): void
    {
        $this->migratePackageTables();
        $token = $this->issueFounderToken();

        $this->writeAddonWithSettings('Cms', [
            'id' => 'cms',
            'code' => 'cms',
            'name' => '内容管理系统',
            'title' => '内容管理系统',
            'version' => '1.0.0',
            'providers' => [],
        ], [
            'enabled' => true,
            'mode' => 'hosted',
            'injection' => [
                'strategy' => 'merge',
            ],
            'sections' => [
                [
                    'key' => 'basic',
                    'title' => '基础配置',
                    'schema' => [
                        'fields' => [
                            [
                                'name' => 'site_name',
                                'type' => 'text',
                                'label' => '站点名称',
                            ],
                            [
                                'name' => 'legacy_flag',
                                'type' => 'switch',
                                'label' => '旧开关',
                            ],
                        ],
                    ],
                    'defaults' => [
                        'site_name' => 'CMS Demo',
                        'legacy_flag' => true,
                    ],
                ],
            ],
        ]);

        Addon::swap(new AddonManager());

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/plugins/cms/sections/basic')
            ->assertOk()
            ->assertJsonPath('data.values.legacy_flag', 1);

        $this->writeAddonWithSettings('Cms', [
            'id' => 'cms',
            'code' => 'cms',
            'name' => '内容管理系统',
            'title' => '内容管理系统',
            'version' => '1.0.1',
            'providers' => [],
        ], [
            'enabled' => true,
            'mode' => 'hosted',
            'injection' => [
                'strategy' => 'overwrite',
            ],
            'sections' => [
                [
                    'key' => 'basic',
                    'title' => '基础配置',
                    'schema' => [
                        'fields' => [
                            [
                                'name' => 'site_name',
                                'type' => 'text',
                                'label' => '站点名称',
                            ],
                        ],
                    ],
                    'defaults' => [
                        'site_name' => 'CMS Demo',
                    ],
                ],
            ],
        ]);

        Addon::swap(new AddonManager());

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/plugins/cms/sections/basic');

        $response->assertOk();
        self::assertArrayNotHasKey('legacy_flag', (array) data_get($response->json(), 'data.values', []));

        self::assertDatabaseMissing('system_configs', [
            'name' => 'legacy_flag',
        ]);
    }

    public function test_plugin_settings_facade_creates_declared_fields_without_defaults(): void
    {
        $this->migratePackageTables();
        $token = $this->issueFounderToken();

        $this->writeAddonWithSettings('Cms', [
            'id' => 'cms',
            'code' => 'cms',
            'name' => '内容管理系统',
            'title' => '内容管理系统',
            'version' => '1.0.0',
            'providers' => [],
        ], [
            'enabled' => true,
            'mode' => 'hosted',
            'sections' => [
                [
                    'key' => 'basic',
                    'title' => '基础配置',
                    'schema' => [
                        'fields' => [
                            [
                                'name' => 'site_name',
                                'type' => 'text',
                                'label' => '站点名称',
                            ],
                            [
                                'name' => 'enabled',
                                'type' => 'switch',
                                'label' => '启用状态',
                            ],
                            [
                                'name' => 'tags',
                                'type' => 'checkbox',
                                'label' => '标签',
                                'options' => [
                                    [
                                        'value' => 'news',
                                        'label' => '资讯',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'defaults' => [
                        'site_name' => 'CMS Demo',
                    ],
                ],
            ],
        ]);

        Addon::swap(new AddonManager());

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/settings/plugins/cms/sections/basic')
            ->assertOk()
            ->assertJsonPath('data.values.site_name', 'CMS Demo')
            ->assertJsonPath('data.values.enabled', 0)
            ->assertJsonPath('data.values.tags', []);

        $this->assertDatabaseHas('system_configs', [
            'name' => 'enabled',
            'value' => '0',
        ]);
        $this->assertDatabaseHas('system_configs', [
            'name' => 'tags',
            'value' => '[]',
        ]);
    }

    /**
     * @return array{0: SystemConfigGroup, 1: SystemConfigGroup}
     */
    private function seedSystemSettingFixtures(): array
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createSystemConfigGroupsTable();
        $this->createSystemConfigsTable();
        $this->migratePackageTables();

        $group = SystemConfigGroup::query()->create([
            'title' => '系统设置',
            'name' => 'system',
            'weight' => 100,
            'extra' => [
                'layout' => [
                    'mode' => 'tab',
                ],
                'icon' => 'Setting',
            ],
            'status' => 1,
        ]);
        $section = SystemConfigGroup::query()->create([
            'title' => '基础配置',
            'name' => 'basic',
            'parent_id' => $group->id,
            'weight' => 90,
            'intro' => '站点基础配置',
            'extra' => [
                'icon' => 'Setting',
                'layout' => [
                    'mode' => 'block',
                    'labelWidth' => 140,
                ],
            ],
            'status' => 1,
        ]);

        SystemConfig::query()->create([
            'title' => '站点标题',
            'name' => 'site_title',
            'system_config_group_id' => $section->id,
            'weight' => 100,
            'type' => 'text',
            'extra' => [
                'meta' => [
                    'placeholder' => '请输入站点标题',
                    'expose' => 'public',
                ],
            ],
            'value' => 'PTAdmin',
            'default_val' => 'PTAdmin',
        ]);
        SystemConfig::query()->create([
            'title' => '登录验证码',
            'name' => 'login_captcha',
            'system_config_group_id' => $section->id,
            'weight' => 90,
            'type' => 'switch',
            'extra' => [
                'meta' => [
                    'expose' => 'private',
                ],
            ],
            'value' => '1',
            'default_val' => '0',
        ]);

        return [$group->refresh(), $section->refresh()];
    }

    /**
     * @param array<string, mixed> $manifest
     * @param array<string, mixed> $settings
     */
    private function writeAddonWithSettings(string $basePath, array $manifest, array $settings): void
    {
        $directory = base_path('addons/'.$basePath);
        File::ensureDirectoryExists($directory.'/Config');

        File::put($directory.'/manifest.json', (string) json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        File::put(
            $directory.'/Config/settings.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($settings, true).";\n"
        );
    }

    /**
     * @param array<string, mixed> $manifest
     * @param array<string, mixed> $config
     */
    private function writeAddonWithLegacyConfig(string $basePath, array $manifest, array $config): void
    {
        $directory = base_path('addons/'.$basePath);
        File::ensureDirectoryExists($directory.'/Config');

        File::put($directory.'/manifest.json', (string) json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        File::put(
            $directory.'/Config/config.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($config, true).";\n"
        );
    }

    private function issueFounderToken(): string
    {
        $founder = $this->createAdminAccount([
            'username' => 'settings-founder',
            'nickname' => 'Settings Founder',
            'is_founder' => 1,
        ]);

        return $this->issueAdminToken($founder);
    }
}
