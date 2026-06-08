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

        $this->withHeaders($this->jsonApiHeaders($token))->postJson('/ptadmin/roles', [
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
            ->getJson('/ptadmin/roles?'.http_build_query([
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

        $this->withHeaders($this->jsonApiHeaders($token))->putJson('/ptadmin/roles/'.$role->id, [
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
            ->getJson('/ptadmin/admin-resources');
        $treeResponse->assertOk()->assertJson([
            'code' => 0,
        ]);

        $this->withHeaders($this->jsonApiHeaders($token))->postJson('/ptadmin/admin-resources', [
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
        self::assertNull($resource->meta_json);

        $detailResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/ptadmin/admin-resources/'.$resource->id);
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
                'meta_json' => null,
            ],
        ]);

        $this->withHeaders($this->jsonApiHeaders($token))->putJson('/ptadmin/admin-resources/'.$resource->id, [
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
            'parent_id' => 0,
            'meta_json' => [
                'note' => '测试资源已更新',
                'controller' => 'UpdatedDashboardController',
                'hidden' => 0,
                'keep_alive' => 1,
            ],
        ])->assertOk()->assertJson([
            'code' => 0,
        ]);

        $resource = $resource->fresh();
        self::assertSame('custom.dashboard', $resource->name);
        self::assertSame('自定义看板-已更新', $resource->title);
        self::assertSame('/custom-dashboard', $resource->route);
        self::assertSame([
            'note' => '测试资源已更新',
            'controller' => 'UpdatedDashboardController',
            'hidden' => 0,
            'keep_alive' => 1,
        ], $resource->meta_json);

        $filteredTree = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/ptadmin/admin-resources?'.http_build_query([
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
        $filteredTreePayload = json_encode($filteredTree->json('data.results'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        self::assertIsString($filteredTreePayload);
        self::assertStringContainsString('"name":"custom.dashboard"', $filteredTreePayload);

        $adminResourceTree = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/ptadmin/admin-resources?'.http_build_query([
                'filters' => [
                    ['field' => 'name', 'operator' => '=', 'value' => 'custom.dashboard'],
                ],
            ]));
        $adminResourceTree->assertOk()->assertJson(['code' => 0]);
        $adminResourcePayload = collect((array) $adminResourceTree->json('data.results'))
            ->firstWhere('name', 'custom.dashboard');
        self::assertIsArray($adminResourcePayload);
        self::assertSame($resource->id, (int) $adminResourcePayload['id']);
        self::assertSame('自定义看板-已更新', $adminResourcePayload['title']);
        self::assertSame('dashboard', $adminResourcePayload['module']);
        self::assertSame('custom.dashboard', $adminResourcePayload['page_key']);
        self::assertSame('/custom-dashboard', $adminResourcePayload['route']);
        self::assertSame('TrendCharts', $adminResourcePayload['icon']);
        self::assertSame('测试资源已更新', data_get($adminResourcePayload, 'meta_json.note'));
        self::assertSame('UpdatedDashboardController', data_get($adminResourcePayload, 'meta_json.controller'));

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/ptadmin/roles-resource/'.$role->id)
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'checked' => [],
                ],
            ]);

        $this->withHeaders($this->jsonApiHeaders($token))->postJson('/ptadmin/roles-resource/'.$role->id, [
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
            ->getJson('/ptadmin/roles-resource/'.$role->id);
        $roleSelection->assertOk();
        self::assertContains($resource->id, (array) $roleSelection->json('data.checked'));

        $roleAssignment = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/ptadmin/resources-role/'.$role->id);
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

        $this->withHeaders($this->jsonApiHeaders($token))->postJson('/ptadmin/resources-admin/'.$member->id, [
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
            ->getJson('/ptadmin/resources-admin/'.$member->id);
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
            ->deleteJson('/ptadmin/admin-resources/'.$resource->id)
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
            ->deleteJson('/ptadmin/roles/'.$role->id)
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
