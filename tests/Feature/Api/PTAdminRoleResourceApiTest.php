<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use PTAdmin\Admin\Models\AdminGrant;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Models\AdminRole;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminRoleResourceApiTest extends TestCase
{
    public function test_role_and_resource_endpoints_can_manage_role_resource_and_user_resource_assignments(): void
    {
        $this->createAdminsTable();
        $this->createUserTokensTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount([
            'username' => 'founder_resource',
            'nickname' => 'Founder',
            'is_founder' => 1,
        ]);
        $member = $this->createAdminAccount([
            'username' => 'member_resource',
            'nickname' => 'Member',
        ]);
        $token = $this->issueAdminToken($founder);

        $this->withHeaders($this->jsonApiHeaders($token))->postJson('/system/roles', [
            'code' => 'custom_role',
            'name' => '自定义角色',
            'note' => '用于测试',
            'status' => 1,
        ])->assertOk()->assertJson([
            'code' => 0,
        ]);

        $role = AdminRole::query()->where('code', 'custom_role')->firstOrFail();
        AdminRole::query()->create([
            'code' => 'custom_role_disabled',
            'name' => '自定义角色-禁用',
            'status' => 0,
            'sort' => 10,
        ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/roles?'.http_build_query([
                'filters' => json_encode([
                    ['field' => 'status', 'operator' => '=', 'value' => 1],
                    ['field' => 'code', 'operator' => 'like', 'value' => '%custom_role%'],
                ], JSON_UNESCAPED_UNICODE),
                'sorts' => json_encode([
                    ['field' => 'id', 'direction' => 'desc'],
                ], JSON_UNESCAPED_UNICODE),
                'limit' => 1,
                'page' => 1,
            ]))
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'total' => 1,
                    'results' => [
                        [
                            'id' => $role->id,
                            'code' => 'custom_role',
                            'name' => '自定义角色',
                            'status' => 1,
                        ],
                    ],
                ],
            ]);

        $this->withHeaders($this->jsonApiHeaders($token))->putJson('/system/roles/'.$role->id, [
            'code' => 'custom_role',
            'name' => '自定义角色-已更新',
            'note' => '更新后',
            'status' => 0,
        ])->assertOk()->assertJson([
            'code' => 0,
        ]);

        self::assertSame('自定义角色-已更新', $role->fresh()->name);
        self::assertSame(0, (int) $role->fresh()->status);

        $treeResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/resources');
        $treeResponse->assertOk()->assertJson([
            'code' => 0,
        ]);

        $this->withHeaders($this->jsonApiHeaders($token))->postJson('/system/resources', [
            'name' => 'custom.dashboard',
            'title' => '自定义看板',
            'module' => 'dashboard',
            'page_key' => 'custom.dashboard',
            'route' => 'custom/dashboard',
            'icon' => 'HomeFilled',
            'weight' => 9,
            'note' => '测试资源',
            'type' => 'nav',
            'status' => 1,
            'is_nav' => 1,
            'controller' => 'CustomDashboardController',
            'parent_id' => 0,
        ])->assertOk()->assertJson([
            'code' => 0,
        ]);

        $resource = AdminResource::query()->where('name', 'custom.dashboard')->firstOrFail();

        $detailResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/resources/'.$resource->id);
        $detailResponse->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'id' => $resource->id,
                'name' => 'custom.dashboard',
                'title' => '自定义看板',
                'module' => 'dashboard',
                'page_key' => 'custom.dashboard',
                'route' => '/custom/dashboard',
                'type' => 'nav',
                'status' => 1,
                'is_nav' => 1,
                'icon' => 'HomeFilled',
                'keep_alive' => 1,
            ],
        ]);

        $this->withHeaders($this->jsonApiHeaders($token))->putJson('/system/resources/'.$resource->id, [
            'name' => 'custom.dashboard',
            'title' => '自定义看板-已更新',
            'module' => 'dashboard',
            'page_key' => 'custom.dashboard',
            'route' => '/custom-dashboard',
            'icon' => 'TrendCharts',
            'weight' => 12,
            'note' => '测试资源已更新',
            'type' => 'nav',
            'status' => 1,
            'is_nav' => 1,
            'controller' => 'UpdatedDashboardController',
            'parent_id' => 0,
        ])->assertOk()->assertJson([
            'code' => 0,
        ]);

        $resource = $resource->fresh();
        self::assertSame('custom.dashboard', $resource->name);
        self::assertSame('自定义看板-已更新', $resource->title);
        self::assertSame('/custom-dashboard', $resource->route);

        $filteredTree = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/resources?'.http_build_query([
                'filters' => [
                    ['field' => 'status', 'operator' => '=', 'value' => 1],
                    ['field' => 'title', 'operator' => 'like', 'value' => '%自定义看板%'],
                ],
                'sorts' => [
                    ['field' => 'id', 'direction' => 'desc'],
                ],
            ]));
        $filteredTree->assertOk()->assertJson([
            'code' => 0,
        ]);
        self::assertSame('custom.dashboard', data_get($filteredTree->json(), 'data.results.0.name'));

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/roles-resource/'.$role->id)
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'checked' => [],
                ],
            ]);

        $this->withHeaders($this->jsonApiHeaders($token))->postJson('/system/roles-resource/'.$role->id, [
            'ids' => [$resource->id],
        ])->assertOk()->assertJson([
            'code' => 0,
        ]);

        self::assertDatabaseHas('admin_grants', [
            'subject_type' => 'role',
            'subject_id' => $role->id,
            'resource_id' => $resource->id,
        ]);

        $roleSelection = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/roles-resource/'.$role->id);
        $roleSelection->assertOk();
        self::assertContains($resource->id, (array) $roleSelection->json('data.checked'));

        $roleAssignment = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/resources-role/'.$role->id);
        $roleAssignment->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'detail' => [
                    'id' => $role->id,
                    'title' => '自定义角色-已更新',
                ],
            ],
        ]);
        self::assertContains($resource->id, (array) $roleAssignment->json('data.resource_ids'));

        $this->withHeaders($this->jsonApiHeaders($token))->postJson('/system/resources-admins/'.$member->id, [
            'resource_ids' => [$resource->id],
        ])->assertOk()->assertJson([
            'code' => 0,
        ]);

        self::assertDatabaseHas('admin_grants', [
            'subject_type' => 'user',
            'subject_id' => $member->id,
            'resource_id' => $resource->id,
        ]);

        $adminAssignment = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/resources-admins/'.$member->id);
        $adminAssignment->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'detail' => [
                    'id' => $member->id,
                    'title' => 'Member',
                ],
            ],
        ]);
        self::assertContains($resource->id, (array) $adminAssignment->json('data.resource_ids'));

        $this->withHeaders($this->jsonApiHeaders($token))
            ->deleteJson('/system/resources/'.$resource->id)
            ->assertOk()
            ->assertJson([
                'code' => 0,
            ]);

        self::assertNotNull(AdminResource::query()->findOrFail($resource->id)->deleted_at);
        self::assertSame(
            0,
            AdminGrant::query()->where('resource_id', $resource->id)->count()
        );

        $this->withHeaders($this->jsonApiHeaders($token))
            ->deleteJson('/system/roles/'.$role->id)
            ->assertOk()
            ->assertJson([
                'code' => 0,
            ]);

        self::assertDatabaseMissing('admin_grants', [
            'subject_type' => 'role',
            'subject_id' => $role->id,
        ]);
    }
}
