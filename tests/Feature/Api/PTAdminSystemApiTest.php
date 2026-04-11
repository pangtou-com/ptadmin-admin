<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use PTAdmin\Admin\Models\AdminRole;
use PTAdmin\Admin\Models\AdminUserRole;
use PTAdmin\Admin\Models\Admin;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminSystemApiTest extends TestCase
{
    public function test_system_endpoints_can_create_update_assign_status_and_delete_users(): void
    {
        $this->createAdminsTable();
        $this->createAdminLoginLogsTable();
        $this->createUserTokensTable();
        $this->migratePackageTables();

        $founder = $this->createAdminAccount([
            'username' => 'founder_system',
            'nickname' => 'Founder',
            'is_founder' => 1,
        ]);
        $token = $this->issueAdminToken($founder);

        $roleA = AdminRole::query()->create([
            'code' => 'ops_a',
            'name' => '运维A',
            'status' => 1,
            'sort' => 1,
        ]);
        $roleB = AdminRole::query()->create([
            'code' => 'ops_b',
            'name' => '运维B',
            'status' => 1,
            'sort' => 2,
        ]);

        $this->withHeaders($this->jsonApiHeaders($token))->postJson('/system/admins', [
            'username' => 'operator',
            'nickname' => 'Operator',
            'password' => 'secret123',
            'mobile' => '13800138000',
            'role_id' => $roleA->id,
        ])->assertOk()->assertJson([
            'code' => 0,
            'message' => '操作成功',
        ]);

        $admin = Admin::query()->where('username', 'operator')->firstOrFail();

        self::assertSame('Operator', $admin->nickname);
        self::assertDatabaseHas('admin_user_roles', [
            'user_id' => $admin->id,
            'role_id' => $roleA->id,
        ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/admins')
            ->assertOk()
            ->assertJson([
                'code' => 0,
            ]);

        $detailResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/admins/'.$admin->id);

        $detailResponse->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'id' => $admin->id,
                'username' => 'operator',
                'nickname' => 'Operator',
                'role_id' => $roleA->id,
            ],
        ]);
        self::assertContains($roleA->id, (array) $detailResponse->json('data.role_ids'));

        $this->withHeaders($this->jsonApiHeaders($token))->putJson('/system/admins/'.$admin->id, [
            'username' => 'operator',
            'nickname' => 'Operator Updated',
            'role_id' => $roleB->id,
            'mobile' => '13800138001',
        ])->assertOk()->assertJson([
            'code' => 0,
        ]);

        $admin = $admin->refresh();
        self::assertSame('Operator Updated', $admin->nickname);

        $updatedDetailResponse = $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/admins/'.$admin->id);
        self::assertSame([$roleB->id], array_values((array) $updatedDetailResponse->json('data.role_ids')));

        $this->withHeaders($this->jsonApiHeaders($token))->postJson('/system/admins-role/'.$admin->id, [
            'role_id' => [$roleA->id, $roleB->id],
        ])->assertOk()->assertJson([
            'code' => 0,
        ]);

        self::assertSame(
            2,
            AdminUserRole::query()->where('user_id', $admin->id)->count()
        );

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/admins-status/'.$admin->id.'?value=0')
            ->assertOk()
            ->assertJson([
                'code' => 0,
            ]);

        self::assertSame(0, (int) $admin->fresh()->status);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->deleteJson('/system/admins/'.$admin->id)
            ->assertOk()
            ->assertJson([
                'code' => 0,
            ]);

        self::assertNotNull(Admin::withTrashed()->findOrFail($admin->id)->deleted_at);
    }
}
