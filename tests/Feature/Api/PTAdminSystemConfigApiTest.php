<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use Illuminate\Support\Facades\Cache;
use PTAdmin\Admin\Models\SystemConfig;
use PTAdmin\Admin\Models\SystemConfigGroup;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminSystemConfigApiTest extends TestCase
{
    public function test_system_config_navigation_endpoint_returns_group_and_section_tree(): void
    {
        [$group, $section] = $this->seedSettingFixtures();
        $token = $this->issueFounderToken();

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/system-configs');

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'groups' => [
                    [
                        'id' => $group->id,
                        'name' => 'system',
                        'title' => '系统配置',
                        'children' => [
                            [
                                'id' => $section->id,
                                'name' => 'basic',
                                'title' => '基础配置',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_system_config_details_endpoint_returns_easy_schema_blueprint_and_runtime_values(): void
    {
        [, $section] = $this->seedSettingFixtures();
        $token = $this->issueFounderToken();

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/system-configs/'.$section->id);

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'group' => [
                    'name' => 'system',
                    'title' => '系统配置',
                ],
                'section' => [
                    'id' => $section->id,
                    'name' => 'basic',
                    'title' => '基础配置',
                ],
                'schema' => [
                    'resource' => [
                        'name' => 'system_basic_settings',
                        'title' => '基础配置',
                    ],
                ],
                'values' => [
                    'site_title' => 'PTAdmin',
                    'site_description' => '后台管理系统',
                    'login_captcha' => 1,
                    'watermark_positions' => ['top_left', 'bottom_right'],
                ],
            ],
        ]);

        self::assertSame('text', $response->json('data.schema.fields.0.type'));
        self::assertSame('switch', $response->json('data.schema.fields.2.type'));
        self::assertSame('checkbox', $response->json('data.schema.fields.3.type'));
        self::assertSame('左上', $response->json('data.schema.fields.3.options.0.label'));
        self::assertSame('top_left', $response->json('data.schema.fields.3.options.0.value'));
    }

    public function test_system_config_update_endpoint_persists_values_and_updates_runtime_cache(): void
    {
        [, $section] = $this->seedSettingFixtures();
        $token = $this->issueFounderToken();

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/system-configs/'.$section->id, [
                'site_title' => 'PTAdmin Next',
                'site_description' => '新的后台描述',
                'login_captcha' => 0,
                'watermark_positions' => ['bottom_left'],
            ]);

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'values' => [
                    'site_title' => 'PTAdmin Next',
                    'site_description' => '新的后台描述',
                    'login_captcha' => 0,
                    'watermark_positions' => ['bottom_left'],
                ],
            ],
        ]);

        self::assertSame('PTAdmin Next', SystemConfig::query()->where('name', 'site_title')->value('value'));
        self::assertSame('0', SystemConfig::query()->where('name', 'login_captcha')->value('value'));
        self::assertSame(
            json_encode(['bottom_left'], JSON_UNESCAPED_UNICODE),
            SystemConfig::query()->where('name', 'watermark_positions')->value('value')
        );

        self::assertSame('PTAdmin Next', system_config('system.basic.site_title'));
        self::assertSame('PTAdmin Next', system_config('basic.site_title'));
        self::assertSame(0, system_config('system.basic.login_captcha'));
        self::assertSame(['bottom_left'], system_config('system.basic.watermark_positions'));
    }

    public function test_system_config_runtime_read_helpers_return_grouped_values(): void
    {
        $this->seedSettingFixtures();

        self::assertSame([
            'basic' => [
                'site_title' => 'PTAdmin',
                'site_description' => '后台管理系统',
                'login_captcha' => 1,
                'watermark_positions' => ['top_left', 'bottom_right'],
            ],
        ], \PTAdmin\Admin\Services\SystemConfigService::byGroupName('system'));

        self::assertSame('fallback', system_config('missing.path', 'fallback'));
    }

    /**
     * 构造一组可覆盖导航、schema、值保存的基础配置数据。
     *
     * @return array{0: SystemConfigGroup, 1: SystemConfigGroup}
     */
    private function seedSettingFixtures(): array
    {
        $this->createSystemsTable();
        $this->createUserTokensTable();
        $this->createSystemConfigGroupsTable();
        $this->createSystemConfigsTable();
        $this->migratePackageTables();

        Cache::forget('systemConfig');

        $group = SystemConfigGroup::query()->create([
            'title' => '系统配置',
            'name' => 'system',
            'weight' => 100,
            'status' => 1,
        ]);
        $section = SystemConfigGroup::query()->create([
            'title' => '基础配置',
            'name' => 'basic',
            'parent_id' => $group->id,
            'weight' => 90,
            'intro' => '站点基础配置',
            'status' => 1,
        ]);

        SystemConfig::query()->create([
            'title' => '站点标题',
            'name' => 'site_title',
            'system_config_group_id' => $section->id,
            'weight' => 100,
            'type' => 'text',
            'intro' => '用于浏览器标题和后台展示',
            'value' => 'PTAdmin',
            'default_val' => 'PTAdmin',
        ]);
        SystemConfig::query()->create([
            'title' => '站点描述',
            'name' => 'site_description',
            'system_config_group_id' => $section->id,
            'weight' => 90,
            'type' => 'textarea',
            'intro' => '用于 SEO 和系统说明',
            'value' => '后台管理系统',
            'default_val' => '后台管理系统',
        ]);
        SystemConfig::query()->create([
            'title' => '登录验证码',
            'name' => 'login_captcha',
            'system_config_group_id' => $section->id,
            'weight' => 80,
            'type' => 'switch',
            'intro' => '控制后台登录是否显示验证码',
            'value' => '1',
            'default_val' => '0',
        ]);
        SystemConfig::query()->create([
            'title' => '水印位置',
            'name' => 'watermark_positions',
            'system_config_group_id' => $section->id,
            'weight' => 70,
            'type' => 'checkbox',
            'intro' => '图片上传后需要叠加水印的位置',
            'extra' => "top_left=左上\nbottom_left=左下\nbottom_right=右下",
            'value' => json_encode(['top_left', 'bottom_right'], JSON_UNESCAPED_UNICODE),
            'default_val' => json_encode(['top_left'], JSON_UNESCAPED_UNICODE),
        ]);

        Cache::forget('systemConfig');

        return [$group->refresh(), $section->refresh()];
    }

    private function issueFounderToken(): string
    {
        $founder = $this->createAdminSystem([
            'username' => 'founder_setting',
            'nickname' => 'Founder Setting',
            'is_founder' => 1,
        ]);

        return $this->issueAdminToken($founder);
    }
}
