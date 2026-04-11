<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use PTAdmin\Admin\Models\SystemConfig;
use PTAdmin\Admin\Models\SystemConfigGroup;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminSystemConfigGroupApiTest extends TestCase
{
    public function test_system_config_group_endpoints_require_admin_login(): void
    {
        $this->createAdminsTable();
        $this->createSystemConfigGroupsTable();
        $this->migratePackageTables();

        $this->withHeaders($this->jsonApiHeaders())
            ->getJson('/system/system-config-groups')
            ->assertOk()
            ->assertJson([
                'code' => 419,
                'message' => '未登录',
            ]);
    }

    public function test_system_config_group_endpoints_can_list_create_edit_and_query_children(): void
    {
        [$root, $basic, $empty] = $this->seedSystemConfigGroupFixtures();
        $token = $this->issueFounderToken();

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/system-config-groups')
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'results' => [
                        [
                            'id' => $root->id,
                            'name' => 'system',
                            'title' => '系统配置',
                        ],
                    ],
                ],
            ]);

        $createResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/system/system-config-groups', [
                'title' => '登录配置',
                'name' => 'login',
                'parent_id' => $root->id,
                'weight' => 60,
                'intro' => '登录相关设置',
                'status' => 1,
            ]);

        $createResponse->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'name' => 'login',
                'title' => '登录配置',
                'parent_id' => $root->id,
            ],
        ]);

        $createdId = (int) $createResponse->json('data.id');

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/system-config-groups/'.$createdId, [
                'title' => '登录与安全',
                'name' => 'login_security',
                'parent_id' => $root->id,
                'weight' => 88,
                'intro' => '登录和安全策略',
                'status' => 1,
            ])
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'id' => $createdId,
                    'name' => 'login_security',
                    'title' => '登录与安全',
                    'parent_id' => $root->id,
                ],
            ]);

        $fullResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/system-config-groups/'.$root->id.'/sections');

        $fullResponse->assertOk()->assertJson([
            'code' => 0,
        ]);

        self::assertCount(3, (array) $fullResponse->json('data'));
        self::assertSame('basic', $fullResponse->json('data.0.category.name'));
        self::assertSame('site_title', $fullResponse->json('data.0.configs.0.name'));
        self::assertSame('login_security', $fullResponse->json('data.1.category.name'));
        self::assertSame([], $fullResponse->json('data.1.configs'));
        self::assertSame('upload', $fullResponse->json('data.2.category.name'));

        $rootResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/system-config-groups/'.$root->id.'/children');

        $rootResponse->assertOk()->assertJson([
            'code' => 0,
        ]);

        self::assertCount(3, (array) $rootResponse->json('data'));
        self::assertSame('basic', $rootResponse->json('data.0.name'));
        self::assertSame('site_title', $rootResponse->json('data.0.children.0.name'));
        self::assertSame('login_security', $rootResponse->json('data.1.name'));
        self::assertSame([], $rootResponse->json('data.1.children'));
        self::assertSame('upload', $rootResponse->json('data.2.name'));

        $this->withHeaders($this->jsonApiHeaders($token))
            ->deleteJson('/system/system-config-groups/'.$empty->id)
            ->assertOk()
            ->assertJson([
                'code' => 0,
            ]);

        self::assertNull(SystemConfigGroup::query()->find($empty->id));
    }

    public function test_system_config_group_delete_endpoint_rejects_groups_with_children_or_configs(): void
    {
        [$root, $basic] = $this->seedSystemConfigGroupFixtures();
        $token = $this->issueFounderToken();

        $this->withHeaders($this->jsonApiHeaders($token))
            ->deleteJson('/system/system-config-groups/'.$root->id)
            ->assertOk()
            ->assertJson([
                'code' => 10000,
                'message' => '请先删除子级配置',
            ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->deleteJson('/system/system-config-groups/'.$basic->id)
            ->assertOk()
            ->assertJson([
                'code' => 10000,
                'message' => '请删除配置项后再删除分类',
            ]);
    }

    public function test_system_config_group_store_endpoint_returns_validation_error_for_invalid_payload(): void
    {
        [$root] = $this->seedSystemConfigGroupFixtures();
        $token = $this->issueFounderToken();

        $this->withHeaders($this->jsonApiHeaders($token))
            ->postJson('/system/system-config-groups', [
                'title' => '',
                'name' => '1invalid-name',
                'parent_id' => $root->id,
                'status' => 9,
            ])
            ->assertStatus(200)
            ->assertJson([
                'code' => 20000,
            ]);
    }

    /**
     * 构造系统配置分组与配置项测试数据。
     *
     * @return array{0: SystemConfigGroup, 1: SystemConfigGroup, 2: SystemConfigGroup}
     */
    private function seedSystemConfigGroupFixtures(): array
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->createSystemConfigGroupsTable();
        $this->createSystemConfigsTable();
        $this->migratePackageTables();

        $root = SystemConfigGroup::query()->create([
            'title' => '系统配置',
            'name' => 'system',
            'weight' => 100,
            'status' => 1,
        ]);

        $basic = SystemConfigGroup::query()->create([
            'title' => '基础配置',
            'name' => 'basic',
            'parent_id' => $root->id,
            'weight' => 90,
            'intro' => '基础系统设置',
            'status' => 1,
        ]);

        $empty = SystemConfigGroup::query()->create([
            'title' => '上传配置',
            'name' => 'upload',
            'parent_id' => $root->id,
            'weight' => 80,
            'intro' => '上传处理设置',
            'status' => 1,
        ]);

        SystemConfig::query()->create([
            'title' => '站点标题',
            'name' => 'site_title',
            'system_config_group_id' => $basic->id,
            'weight' => 100,
            'type' => 'text',
            'value' => 'PTAdmin',
            'default_val' => 'PTAdmin',
        ]);

        return [$root->refresh(), $basic->refresh(), $empty->refresh()];
    }

    private function issueFounderToken(): string
    {
        $founder = $this->createAdminAccount([
            'username' => 'founder_system_config_group',
            'nickname' => 'Founder Group',
            'is_founder' => 1,
        ]);

        return $this->issueAdminToken($founder);
    }
}
