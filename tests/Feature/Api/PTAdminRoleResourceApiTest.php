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
        $this->createSystemsTable();
        $this->createUserTokensTable();
        $this->migratePackageTables();

        $founder = $this->createAdminSystem([
            'username' => 'founder_resource',
            'nickname' => 'Founder',
            'is_founder' => 1,
        ]);
        $member = $this->createAdminSystem([
            'username' => 'member_resource',
            'nickname' => 'Member',
        ]);
        $token = $this->issueAdminToken($founder);

        $this->withHeaders($this->jsonApiHeaders($token))->postJson('/system/roles', [
            'name' => 'custom_role',
            'title' => '自定义角色',
            'note' => '用于测试',
            'status' => 1,
        ])->assertOk()->assertJson([
            'code' => 0,
        ]);

        $role = AdminRole::query()->where('code', 'custom_role')->firstOrFail();

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/roles')
            ->assertOk()
            ->assertJson([
                'code' => 0,
            ]);

        $this->withHeaders($this->jsonApiHeaders($token))->putJson('/system/roles/'.$role->id, [
            'name' => 'custom_role',
            'title' => '自定义角色-已更新',
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
            'route' => 'custom/dashboard',
            'component' => 'custom/dashboard/index',
            'icon' => 'layui-icon-home',
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

        $resource = AdminResource::query()->where('code', 'custom.dashboard')->firstOrFail();

        $detailResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/resources/'.$resource->id);
        $detailResponse->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'id' => $resource->id,
                'name' => 'custom.dashboard',
                'title' => '自定义看板',
                'route' => 'custom/dashboard',
                'type' => 'nav',
                'status' => 1,
                'is_nav' => 1,
            ],
        ]);

        $this->withHeaders($this->jsonApiHeaders($token))->putJson('/system/resources/'.$resource->id, [
            'name' => 'custom.dashboard',
            'title' => '自定义看板-已更新',
            'route' => 'custom/dashboard',
            'component' => 'custom/dashboard/show',
            'icon' => 'layui-icon-chart',
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
        self::assertSame('自定义看板-已更新', $resource->name);
        self::assertSame('custom/dashboard', $resource->route);

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

        $this->withHeaders($this->jsonApiHeaders($token))->postJson('/system/resources-system/'.$member->id, [
            'resource_ids' => [$resource->id],
        ])->assertOk()->assertJson([
            'code' => 0,
        ]);

        self::assertDatabaseHas('admin_grants', [
            'subject_type' => 'user',
            'subject_id' => $member->id,
            'resource_id' => $resource->id,
        ]);

        $systemAssignment = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/resources-system/'.$member->id);
        $systemAssignment->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'detail' => [
                    'id' => $member->id,
                    'title' => 'Member',
                ],
            ],
        ]);
        self::assertContains($resource->id, (array) $systemAssignment->json('data.resource_ids'));

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
