<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use Illuminate\Support\Facades\Cache;
use PTAdmin\Admin\Models\SystemConfig;
use PTAdmin\Admin\Models\SystemConfigGroup;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminSystemConfigItemApiTest extends TestCase
{
    public function test_system_config_item_endpoints_can_page_store_edit_save_and_delete(): void
    {
        [$group, $section, $config] = $this->seedSystemConfigItemFixtures();
        $token = $this->issueFounderToken();

        $pageResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/system-config-items');

        $pageResponse->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'total' => 1,
            ],
        ]);

        self::assertSame('site_title', $pageResponse->json('data.results.0.name'));
        self::assertSame((string) $section->id, (string) $pageResponse->json('data.results.0.system_config_group_id'));
        self::assertSame('基础配置', $pageResponse->json('data.results.0.category.title'));

        $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/system/system-config-items', [
                'system_config_group_id' => $section->id,
                'title' => '站点关键字',
                'name' => 'site_keywords',
                'type' => 'text',
                'value' => 'ptadmin',
                'default_val' => 'ptadmin',
                'weight' => 60,
                'intro' => '用于 SEO 关键字配置',
            ])
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'message' => '操作成功',
            ]);

        $created = SystemConfig::query()->where('name', 'site_keywords')->firstOrFail();
        self::assertSame('站点关键字', $created->title);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/system-config-items/'.$created->id, [
                'system_config_group_id' => $section->id,
                'title' => '站点关键字更新',
                'name' => 'site_keywords',
                'type' => 'textarea',
                'value' => 'ptadmin,admin',
                'default_val' => 'ptadmin',
                'weight' => 66,
                'intro' => '更新后的关键字配置',
            ])
            ->assertOk()
            ->assertJson([
                'code' => 0,
            ]);

        $created = $created->refresh();
        self::assertSame('站点关键字更新', $created->title);
        self::assertSame('textarea', $created->type);
        self::assertSame(66, (int) $created->weight);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/system/system-config-items/values', [
                'ids' => [$section->id],
                'basic_site_title' => 'PTAdmin Legacy Save',
                'basic_site_keywords' => 'legacy,keywords',
            ])
            ->assertOk()
            ->assertJson([
                'code' => 0,
            ]);

        self::assertSame('PTAdmin Legacy Save', SystemConfig::query()->findOrFail($config->id)->value);
        self::assertSame('legacy,keywords', $created->fresh()->value);
        self::assertSame('PTAdmin Legacy Save', system_config('system.basic.site_title'));

        $this->withHeaders($this->jsonApiHeaders($token))
            ->deleteJson('/system/system-config-items/'.$created->id)
            ->assertOk()
            ->assertJson([
                'code' => 0,
            ]);

        self::assertNull(SystemConfig::query()->find($created->id));
    }

    public function test_system_config_item_store_endpoint_returns_validation_error_for_invalid_payload(): void
    {
        [, $section] = $this->seedSystemConfigItemFixtures();
        $token = $this->issueFounderToken();

        $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/system/system-config-items', [
                'system_config_group_id' => $section->id,
                'title' => '',
                'name' => '1invalid-name',
                'type' => '',
            ])
            ->assertStatus(200)
            ->assertJson([
                'code' => 20000,
            ]);
    }

    public function test_system_config_item_list_endpoint_supports_filters(): void
    {
        [, $section] = $this->seedSystemConfigItemFixtures();
        $token = $this->issueFounderToken();

        SystemConfig::query()->create([
            'system_config_group_id' => $section->id,
            'title' => '登录验证码',
            'name' => 'login_captcha',
            'type' => 'switch',
            'value' => '1',
            'default_val' => '0',
            'weight' => 90,
            'intro' => '登录验证码开关',
        ]);

        $response = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/system-config-items?system_config_group_id='.$section->id.'&title=登录&name=login&type=switch');

        $response->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'total' => 1,
            ],
        ]);

        self::assertSame('login_captcha', $response->json('data.results.0.name'));
        self::assertSame('switch', $response->json('data.results.0.type'));
    }

    /**
     * 构造一组配置项管理测试数据。
     *
     * @return array{0: SystemConfigGroup, 1: SystemConfigGroup, 2: SystemConfig}
     */
    private function seedSystemConfigItemFixtures(): array
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
            'status' => 1,
        ]);

        $config = SystemConfig::query()->create([
            'system_config_group_id' => $section->id,
            'title' => '站点标题',
            'name' => 'site_title',
            'type' => 'text',
            'value' => 'PTAdmin',
            'default_val' => 'PTAdmin',
            'weight' => 100,
            'intro' => '站点标题配置',
        ]);

        Cache::forget('systemConfig');

        return [$group->refresh(), $section->refresh(), $config->refresh()];
    }

    private function issueFounderToken(): string
    {
        $founder = $this->createAdminSystem([
            'username' => 'founder_system_config_item',
            'nickname' => 'Founder Item',
            'is_founder' => 1,
        ]);

        return $this->issueAdminToken($founder);
    }
}
